<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasLabel;

enum ReturnType: string implements HasLabel
{
    case Return = 'return';
    case Exchange = 'exchange';

    public function getLabel(): string
    {
        return match ($this) {
            self::Return => 'Devolución',
            self::Exchange => 'Cambio',
        };
    }
}
