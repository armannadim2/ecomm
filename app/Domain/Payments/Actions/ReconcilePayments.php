<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\Log;

/**
 * Runs every few minutes (payments:reconcile):
 *  1. PayPal attempts still pending are checked with PayPal (customer approved but never came back → capture).
 *     Redsys has no pull API on this integration: it always notifies us, so nothing to ask.
 *  2. Unpaid online orders whose reservation ended (+ a grace period for late bank answers) are
 *     cancelled and their stock goes back on sale.
 *
 * @return array{captured: int, expired: int}
 */
class ReconcilePayments
{
    /** Extra minutes after the reservation ends, so a payment finished at the last moment still counts. */
    public const GRACE_MINUTES = 15;

    public function __construct(
        private readonly CapturePayPalPayment $capture,
        private readonly ChangeOrderStatus $changeStatus,
    ) {}

    public function handle(): array
    {
        $captured = 0;

        Payment::query()
            ->where('gateway', Gateway::PayPal)
            ->where('status', PaymentAttemptStatus::Pending)
            ->whereNotNull('reference')
            ->where('created_at', '<', now()->subMinutes(3))
            ->with('order')
            ->limit(50)
            ->get()
            ->each(function (Payment $payment) use (&$captured) {
                try {
                    $captured += $this->capture->handle($payment, fromReconcile: true) ? 1 : 0;
                } catch (DomainRuleException $e) {
                    Log::warning('PayPal reconcile failed', ['payment' => $payment->ulid, 'error' => $e->getMessage()]);
                }
            });

        $expired = 0;

        Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', now()->subMinutes(self::GRACE_MINUTES))
            ->limit(100)
            ->get()
            ->each(function (Order $order) use (&$expired) {
                try {
                    $this->changeStatus->handle($order, OrderStatus::Cancelled, ChangeOrderStatus::CANCEL_PAYMENT_EXPIRED);
                    $expired++;
                } catch (DomainRuleException $e) {
                    Log::warning('Could not expire unpaid order', ['order' => $order->number, 'error' => $e->getMessage()]);
                }
            });

        return ['captured' => $captured, 'expired' => $expired];
    }
}
