<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Models\Payment;

/**
 * Records a refused or abandoned payment attempt. The order stays "pending payment" with its stock
 * reserved, so the customer can try again until the reservation ends.
 */
class FailPayment
{
    /** @param  array<string, mixed>  $gatewayData */
    public function handle(Payment $payment, PaymentAttemptStatus $status, ?string $responseCode = null, ?string $reason = null, array $gatewayData = []): Payment
    {
        // Only pending attempts change: a late "denied" never overrides a payment already confirmed.
        Payment::query()
            ->whereKey($payment->id)
            ->where('status', PaymentAttemptStatus::Pending)
            ->update([
                'status' => $status,
                'response_code' => $responseCode,
                'failure_reason' => $reason,
                'gateway_data' => $gatewayData === [] ? null : json_encode($gatewayData),
                'failed_at' => now(),
            ]);

        return $payment->refresh();
    }
}
