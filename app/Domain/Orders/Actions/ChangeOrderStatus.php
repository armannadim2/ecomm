<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Mail\OrderReadyForPickup;
use App\Domain\Orders\Mail\OrderShipped;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Moves an order to its next status. Cancelling puts the stock back.
 * Completing a pay-in-store order marks it as paid (the customer paid at the counter) and issues the invoice.
 * Shipping can carry the carrier and tracking number, which go in the "your order is on its way" e-mail.
 */
class ChangeOrderStatus
{
    public function __construct(private readonly AdjustStock $adjustStock) {}

    public const CANCEL_BY_STAFF = 'staff';

    public const CANCEL_PAYMENT_EXPIRED = 'payment_expired';

    public const CANCEL_BY_CUSTOMER = 'customer';

    /** @param  array{carrier?: ?string, tracking_number?: ?string, tracking_url?: ?string}  $shipping */
    public function handle(Order $order, OrderStatus $to, string $cancelReason = self::CANCEL_BY_STAFF, array $shipping = []): Order
    {
        if (! in_array($to, $order->status->next($order->fulfillment), true)) {
            throw new DomainRuleException("No se puede pasar de «{$order->status->getLabel()}» a «{$to->getLabel()}».");
        }

        $paidNow = false;

        $order = DB::transaction(function () use ($order, $to, $cancelReason, $shipping, &$paidNow): Order {
            $changes = ['status' => $to];

            match ($to) {
                OrderStatus::ReadyForPickup, OrderStatus::Shipped => $changes['ready_at'] = now(),
                OrderStatus::Completed => $changes['completed_at'] = now(),
                OrderStatus::Cancelled => [$changes['cancelled_at'] = now(), $changes['cancel_reason'] = $cancelReason],
                default => null,
            };

            if ($to === OrderStatus::Shipped) {
                $changes += array_filter([
                    'carrier' => trim((string) ($shipping['carrier'] ?? '')) ?: null,
                    'tracking_number' => trim((string) ($shipping['tracking_number'] ?? '')) ?: null,
                    'tracking_url' => filter_var($shipping['tracking_url'] ?? null, FILTER_VALIDATE_URL) && str_starts_with((string) $shipping['tracking_url'], 'https://') ? $shipping['tracking_url'] : null,
                ]);
            }

            if ($to === OrderStatus::Completed && $order->payment_method === PaymentMethod::PayInStore) {
                $changes['payment_status'] = PaymentStatus::Paid;
                $changes['paid_at'] = now();
                $paidNow = true;
            }

            if ($to === OrderStatus::Cancelled) {
                $order->payments()->where('status', PaymentAttemptStatus::Pending)->update(['status' => PaymentAttemptStatus::Expired]);
                $changes['payment_expires_at'] = null;

                foreach ($order->items()->with('variant')->get() as $item) {
                    if ($item->variant && $item->variant->track_stock) {
                        $this->adjustStock->handle($item->variant, $item->quantity, 'Pedido '.$order->number.' cancelado');
                    }
                }
            }

            $order->update($changes);

            // Click & Collect: tell the customer the order is waiting at the counter (sent after commit).
            if ($to === OrderStatus::ReadyForPickup && $order->email) {
                Mail::to($order->email)->locale($order->locale)->queue(new OrderReadyForPickup($order));
            }

            if ($to === OrderStatus::Shipped && $order->email) {
                Mail::to($order->email)->locale($order->locale)->queue(new OrderShipped($order));
            }

            return $order;
        });

        if ($paidNow) {
            OrderPaid::dispatch($order);
        }

        return $order;
    }
}
