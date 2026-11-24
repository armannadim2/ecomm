<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Payment methods. Online methods (Bizum and card through Redsys, PayPal) are shown as "coming soon"
 * until their gateway is built (Phase 2 part 2b); only paying at the shop counter works today.
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

    /** Whether customers can choose it right now. */
    public function isAvailable(): bool
    {
        // No online gateway is connected yet.
        return ! $this->isOnline();
    }
}
