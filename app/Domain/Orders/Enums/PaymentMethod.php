<?php

namespace App\Domain\Orders\Enums;

use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Settings\PaymentSettings;
use Filament\Support\Contracts\HasLabel;

/**
 * Payment methods. Card and Bizum go through Redsys, PayPal through PayPal. An online method is offered
 * only when it is switched on and its credentials are filled in ("Pedidos → Pagos online").
 */
enum PaymentMethod: string implements HasLabel
{
    case Bizum = 'bizum';
    case Card = 'card';
    case PayPal = 'paypal';
    case PayInStore = 'pay_in_store';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bizum => 'Bizum',
            self::Card => 'Tarjeta',
            self::PayPal => 'PayPal',
            self::PayInStore => 'Pago en tienda',
        };
    }

    public function isOnline(): bool
    {
        return $this !== self::PayInStore;
    }

    public function gateway(): ?Gateway
    {
        return match ($this) {
            self::Card, self::Bizum => Gateway::Redsys,
            self::PayPal => Gateway::PayPal,
            self::PayInStore => null,
        };
    }

    /** Whether customers can choose it right now (pay in store also depends on the checkout settings). */
    public function isAvailable(): bool
    {
        $settings = app(PaymentSettings::class);

        return match ($this) {
            self::Card => $settings->cardReady(),
            self::Bizum => $settings->bizumReady(),
            self::PayPal => $settings->paypalReady(),
            self::PayInStore => true,
        };
    }
}
