<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasLabel;

enum Fulfillment: string implements HasLabel
{
    /** Click & Collect at the physical shop. */
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pickup => 'Recogida en tienda',
            self::Delivery => 'Envío a domicilio',
        };
    }
}
