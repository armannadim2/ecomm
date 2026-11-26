<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Gateways\Redsys\RedsysGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentGateways;
use App\Domain\Payments\PaymentRedirect;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

/**
 * Starts (or restarts, after a failed card) the online payment of an order:
 * creates a payment attempt and returns where to send the customer.
 */
class StartPayment
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    public function handle(Order $order): PaymentRedirect
    {
        $method = $order->payment_method;
        $gateway = $method->gateway();

        if ($gateway === null) {
            throw new DomainRuleException(__('Este pedido se paga en la tienda.'));
        }

        if ($order->status !== OrderStatus::PendingPayment || $order->payment_status->isPaid()) {
            throw new DomainRuleException(__('Este pedido ya no está pendiente de pago.'));
        }

        if ($order->payment_expires_at?->isPast()) {
            throw new DomainRuleException(__('El tiempo para pagar este pedido ha terminado. Vuelve a hacer el pedido desde la cesta.'));
        }

        if (! $method->isAvailable()) {
            throw new DomainRuleException(__('Esta forma de pago no está disponible.'));
        }

        $payment = DB::transaction(function () use ($order, $gateway, $method): Payment {
            // An older attempt left open (customer went back and tried again) can no longer be paid.
            // Redsys may still notify it later: ConfirmPayment accepts that as long as the order is unpaid.
            $order->payments()->where('status', PaymentAttemptStatus::Pending)
                ->where('gateway', '!=', $gateway)
                ->update(['status' => PaymentAttemptStatus::Cancelled]);

            $payment = $order->payments()->create([
                'gateway' => $gateway,
                'method' => $method,
                'amount_cents' => $order->total_cents,
                'status' => PaymentAttemptStatus::Pending,
            ]);

            if ($gateway === Gateway::Redsys) {
                $payment->update(['reference' => RedsysGateway::newReference($payment->id)]);
            }

            return $payment;
        });

        try {
            return $this->gateways->for($gateway)->start($payment->setRelation('order', $order));
        } catch (DomainRuleException $e) {
            $payment->update(['status' => PaymentAttemptStatus::Failed, 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);

            throw new DomainRuleException(__('No hemos podido conectar con la pasarela de pago. Inténtalo de nuevo en unos minutos.'), previous: $e);
        }
    }
}
