<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Why the customer returns. Defective or wrong products fall under the 3-year legal guarantee
 * (no 14-day limit, no cost for the customer); the rest is the 14-day right of withdrawal
 * ("Envíos, recogida y devoluciones" legal page).
 */
enum ReturnReason: string implements HasLabel
{
    case Defective = 'defective';
    case WrongItem = 'wrong_item';
    case ChangedMind = 'changed_mind';
    case SizeFit = 'size_fit';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Defective => 'Llegó defectuoso o no funciona',
            self::WrongItem => 'No es el producto que pedí',
            self::ChangedMind => 'Ya no lo quiero',
            self::SizeFit => 'No es el tamaño, color o modelo adecuado',
            self::Other => 'Otro motivo',
        };
    }

    /** Covered by the legal guarantee instead of the 14-day withdrawal. */
    public function isGuarantee(): bool
    {
        return in_array($this, [self::Defective, self::WrongItem], true);
    }
}
