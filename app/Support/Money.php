<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money helpers. All amounts are stored as integer cents (EUR).
 */
final class Money
{
    public static function toCents(string|int|float|null $euros): ?int
    {
        if ($euros === null || $euros === '') {
            return null;
        }

        $normalized = str_replace(',', '.', (string) $euros);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("Invalid amount: {$euros}");
        }

        return (int) round(((float) $normalized) * 100);
    }

    public static function toEuros(?int $cents): ?string
    {
        return $cents === null ? null : number_format($cents / 100, 2, '.', '');
    }

    public static function format(?int $cents): string
    {
        if ($cents === null) {
            return '—';
        }

        return number_format($cents / 100, 2, ',', '.').' €';
    }
}
