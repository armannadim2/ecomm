<?php

namespace App\Domain\Catalog\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Spanish IVA classes (Peninsula + Balearic Islands).
 * Other tax zones (Canarias, Ceuta, Melilla, EU) belong to the tax module.
 */
enum TaxClass: string implements HasLabel
{
    case General = 'general';
    case Reduced = 'reduced';
    case SuperReduced = 'super_reduced';

    public function rate(): int
    {
        return match ($this) {
            self::General => 21,
            self::Reduced => 10,
            self::SuperReduced => 4,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::General => 'General (21 %)',
            self::Reduced => 'Reducido (10 %)',
            self::SuperReduced => 'Superreducido (4 %)',
        };
    }
}
