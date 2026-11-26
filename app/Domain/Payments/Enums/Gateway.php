<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasLabel;

enum Gateway: string implements HasLabel
{
    case Redsys = 'redsys';
    case PayPal = 'paypal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Redsys => 'Redsys',
            self::PayPal => 'PayPal',
        };
    }
}
