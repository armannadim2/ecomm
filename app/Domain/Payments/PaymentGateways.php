<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Gateways\PayPal\PayPalGateway;
use App\Domain\Payments\Gateways\Redsys\RedsysGateway;

/** Finds the gateway implementation for a payment. Tests swap implementations through the container. */
class PaymentGateways
{
    public function for(Gateway $gateway): PaymentGateway
    {
        return match ($gateway) {
            Gateway::Redsys => app(RedsysGateway::class),
            Gateway::PayPal => app(PayPalGateway::class),
        };
    }
}
