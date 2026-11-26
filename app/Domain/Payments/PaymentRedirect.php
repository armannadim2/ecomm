<?php

namespace App\Domain\Payments;

/** Where to send the customer to pay: a plain redirect (PayPal) or an auto-submitted POST form (Redsys). */
final class PaymentRedirect
{
    /** @param  array<string, string>  $fields */
    public function __construct(
        public readonly string $url,
        public readonly string $method = 'GET',
        public readonly array $fields = [],
    ) {}

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
