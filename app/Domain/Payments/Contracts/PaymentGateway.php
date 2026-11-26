<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentRedirect;

/**
 * A hosted payment gateway (ADR-0003): the customer types card or account data on the gateway's
 * page, never on ours. An order is marked paid only from a verified server-to-server answer.
 */
interface PaymentGateway
{
    /** Prepares the payment at the gateway and says where to send the customer. */
    public function start(Payment $payment): PaymentRedirect;

    /**
     * Sends money back to the original card or account.
     * Returns the gateway's reference for the refund; throws DomainRuleException when the gateway refuses.
     */
    public function refund(Payment $payment, int $amountCents): string;
}
