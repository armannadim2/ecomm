<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Gateways\PayPal\PayPalGateway;
use App\Domain\Payments\Models\Payment;

/**
 * Charges an approved PayPal order and records the result. Used when the customer comes back from
 * PayPal, by the CHECKOUT.ORDER.APPROVED webhook and by the reconcile job; safe to run more than once.
 * Returns true when the payment is (now) paid.
 */
class CapturePayPalPayment
{
    public function __construct(
        private readonly PayPalGateway $paypal,
        private readonly ConfirmPayment $confirm,
        private readonly FailPayment $fail,
    ) {}

    public function handle(Payment $payment, bool $fromReconcile = false): bool
    {
        if ($payment->status === PaymentAttemptStatus::Paid) {
            return true;
        }

        if ($payment->status !== PaymentAttemptStatus::Pending || ! $payment->reference) {
            return false;
        }

        if ($fromReconcile) {
            $state = $this->paypal->orderStatus($payment);

            // CREATED / PAYER_ACTION_REQUIRED: the customer has not approved yet — nothing to do.
            if (! in_array($state['status'], ['APPROVED', 'COMPLETED'], true)) {
                return false;
            }

            if ($state['status'] === 'COMPLETED' && $state['capture_id']) {
                return $this->record($payment, $state);
            }
        }

        $result = $this->paypal->capture($payment);

        if ($result['status'] === 'COMPLETED' || ($result['status'] === 'PENDING' && $result['capture_id'])) {
            // PENDING captures (e-check, review) are confirmed later by the PAYMENT.CAPTURE.COMPLETED webhook.
            return $result['status'] === 'COMPLETED' ? $this->record($payment, $result) : false;
        }

        $this->fail->handle($payment, PaymentAttemptStatus::Failed, $result['status'], 'PayPal: '.$result['status']);

        return false;
    }

    /** @param  array{status: string, capture_id: ?string, amount_cents: ?int, currency: ?string}  $result */
    private function record(Payment $payment, array $result): bool
    {
        if ($result['currency'] !== 'EUR') {
            $this->fail->handle($payment, PaymentAttemptStatus::Failed, 'CURRENCY', 'Moneda distinta de EUR: '.$result['currency']);

            return false;
        }

        $this->confirm->handle($payment, (int) $result['amount_cents'], $result['capture_id'], $result['status'], [
            'paypal_order_id' => $payment->reference,
            'capture_id' => $result['capture_id'],
            'status' => $result['status'],
        ]);

        return $payment->refresh()->status === PaymentAttemptStatus::Paid;
    }
}
