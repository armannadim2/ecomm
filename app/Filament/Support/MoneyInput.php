<?php

namespace App\Filament\Support;

use App\Support\Money;
use Filament\Forms\Components\TextInput;

/**
 * A euro amount field. Staff type "19,99"; the database stores 1999 (cents).
 */
final class MoneyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->inputMode('decimal')
            ->step('0.01')
            ->minValue(0)
            ->suffix('€')
            ->formatStateUsing(fn ($state) => Money::toEuros($state === null ? null : (int) $state))
            ->dehydrateStateUsing(fn ($state) => Money::toCents($state));
    }
}
