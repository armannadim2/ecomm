<?php

namespace App\Domain\Invoices\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Spanish invoice types (Real Decreto 1619/2012):
 *  - simplified ("factura simplificada", series S): consumers, up to 400 € (3.000 € in retail);
 *  - full ("factura completa", series F): the buyer gave a NIF and an address;
 *  - corrective ("factura rectificativa", series R): refunds and corrections, always referring to the original.
 */
enum InvoiceType: string implements HasLabel
{
    case Simplified = 'simplified';
    case Full = 'full';
    case Corrective = 'corrective';

    /** Retail sales may use a simplified invoice up to this amount (art. 4.1 RD 1619/2012). */
    public const SIMPLIFIED_LIMIT_CENTS = 300000;

    public function getLabel(): string
    {
        return match ($this) {
            self::Simplified => 'Factura simplificada',
            self::Full => 'Factura',
            self::Corrective => 'Factura rectificativa',
        };
    }

    public function series(): string
    {
        return match ($this) {
            self::Simplified => 'S',
            self::Full => 'F',
            self::Corrective => 'R',
        };
    }
}
