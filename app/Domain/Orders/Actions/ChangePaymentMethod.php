<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Orders\StaffNotifier;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The customer left the payment page without paying and picks another payment method.
 *
 * Online → online: only the method changes; the order stays reserved until payment_expires_at.
 * Online → pay in store (pickup orders, when switched on): the order is confirmed like a
 * pay-in-store order placed at checkout (receipt e-mail, staff notified). Open payment attempts
 * are cancelled; a late bank confirmation is still accepted by ConfirmPayment while unpaid.
 */
class ChangePaymentMethod
{
    public function __construct(
        private readonly CheckoutSettings $settings,
        private readonly StaffNotifier $notifier,
    ) {}

    public function handle(Order $order, PaymentMethod $method): Order
    {
        $confirmed = DB::transaction(function () use ($order, $method): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($order->status !== OrderStatus::PendingPayment || $order->payment_status->isPaid()) {
                throw new DomainRuleException(__('Este pedido ya no está pendiente de pago.'));
            }

            if ($order->payment_expires_at?->isPast()) {
                throw new DomainRuleException(__('El tiempo para pagar este pedido ha terminado. Vuelve a hacer el pedido desde la cesta.'));
            }

            if (! self::canUse($order, $method, $this->settings)) {
                throw new DomainRuleException(__('Esta forma de pago no está disponible.'));
            }

            if ($method->isOnline()) {
                $order->update(['payment_method' => $method]);

                return false;
            }

            $order->payments()->where('status', PaymentAttemptStatus::Pending)
                ->update(['status' => PaymentAttemptStatus::Cancelled]);

            $order->update([
                'payment_method' => $method,
                'status' => OrderStatus::Confirmed,
                'payment_expires_at' => null,
            ]);

            return true;
        });

        $order->refresh();

        if ($confirmed) {
            Mail::to($order->email)->locale($order->locale)->queue(new OrderPlaced($order));
            $this->notifier->newOrder($order);
        }

        return $order;
    }

    /** Methods this order can switch to (shown on the order page). */
    public static function canUse(Order $order, PaymentMethod $method, CheckoutSettings $settings): bool
    {
        if (! $method->isAvailable()) {
            return false;
        }

        return $method->isOnline()
            || ($settings->pay_in_store_enabled && $order->fulfillment === Fulfillment::Pickup);
    }
}
