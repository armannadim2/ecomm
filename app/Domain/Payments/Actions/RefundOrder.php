<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Events\OrderRefunded;
use App\Domain\Orders\Mail\OrderRefunded as OrderRefundedMail;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\PaymentRefund;
use App\Domain\Payments\PaymentGateways;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Gives money back to a customer, in full or in part.
 * Online payments go back through the gateway to the same card / account; with $manual (or for
 * pay-in-store orders) staff have already returned it in cash or by transfer and we only record it.
 * Every refund issues a corrective invoice (OrderRefunded event) and e-mails the customer.
 */
class RefundOrder
{
    public function __construct(private readonly PaymentGateways $gateways) {}

    public function handle(Order $order, int $amountCents, string $reason, ?Staff $staff = null, bool $manual = false, ?ReturnRequest $returnRequest = null): PaymentRefund
    {
        if ($amountCents < 1) {
            throw new DomainRuleException('Indica un importe mayor que 0.');
        }

        if (trim($reason) === '') {
            throw new DomainRuleException('Indica el motivo de la devolución (aparece en la factura rectificativa).');
        }

        if (! $order->payment_status->isPaid()) {
            throw new DomainRuleException('Este pedido no está pagado: no hay nada que devolver.');
        }

        if ($amountCents > $order->refundableCents()) {
            throw new DomainRuleException('Como máximo puedes devolver '.Money::format($order->refundableCents()).'.');
        }

        $payment = $manual || ! $order->payment_method->isOnline() ? null : $this->refundablePayment($order, $amountCents);
        $gatewayReference = null;

        // The gateway is called before writing anything: if it refuses, nothing is recorded.
        if ($payment) {
            $gatewayReference = $this->gateways->for($payment->gateway)->refund($payment, $amountCents);
        }

        $refund = DB::transaction(function () use ($order, $payment, $amountCents, $reason, $staff, $returnRequest, $gatewayReference): PaymentRefund {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            $refund = $locked->refunds()->create([
                'payment_id' => $payment?->id,
                'staff_id' => $staff?->id,
                'return_request_id' => $returnRequest?->id,
                'amount_cents' => $amountCents,
                'channel' => $payment ? PaymentRefund::CHANNEL_GATEWAY : PaymentRefund::CHANNEL_MANUAL,
                'reason' => trim($reason),
                'gateway_reference' => $gatewayReference,
            ]);

            if ($payment) {
                $payment->increment('refunded_cents', $amountCents);
            }

            $refunded = $locked->refunded_cents + $amountCents;
            $locked->update([
                'refunded_cents' => $refunded,
                'payment_status' => $refunded >= $locked->total_cents ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
            ]);

            activity('orders')->performedOn($locked)->causedBy($staff)
                ->withProperties(['amount_cents' => $amountCents, 'channel' => $refund->channel, 'reason' => $refund->reason])
                ->event('refunded')
                ->log('Reembolso de '.Money::format($amountCents));

            return $refund;
        });

        $order->refresh();
        OrderRefunded::dispatch($order, $refund);

        if ($order->email) {
            Mail::to($order->email)->locale($order->locale)->queue(new OrderRefundedMail($order, $refund));
        }

        return $refund;
    }

    /** The paid attempt with enough money left on it (normally the only one). */
    private function refundablePayment(Order $order, int $amountCents): Payment
    {
        $payment = $order->payments()
            ->where('status', PaymentAttemptStatus::Paid)
            ->get()
            ->first(fn (Payment $p) => $p->refundableCents() >= $amountCents);

        return $payment ?? throw new DomainRuleException('No hay ningún pago online con saldo suficiente para devolver ese importe. Márcalo como devolución manual si ya lo has devuelto tú.');
    }
}
