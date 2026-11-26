<?php

namespace App\Domain\Orders\Enums;

use App\Domain\Orders\Settings\CheckoutSettings;
use App\Support\SpanishProvinces;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a home delivery goes. Canarias, Ceuta and Melilla are not served (customs/IGIC),
 * so they have no zone and the checkout refuses them.
 */
enum ShippingZone: string implements HasLabel
{
    /** The shop's own delivery round (postal codes listed in "Envíos y pagos"). */
    case Local = 'local';
    case Peninsula = 'peninsula';
    case Balearics = 'balearics';

    public function getLabel(): string
    {
        return match ($this) {
            self::Local => 'Entrega local',
            self::Peninsula => 'Península',
            self::Balearics => 'Baleares',
        };
    }

    public static function fromPostalCode(?string $postalCode, CheckoutSettings $settings): ?self
    {
        $postalCode = trim((string) $postalCode);

        if (! preg_match('/^\d{5}$/', $postalCode) || ! SpanishProvinces::isShippable($postalCode)) {
            return null;
        }

        if ($settings->local_delivery_enabled && self::inList($postalCode, $settings->local_delivery_postcodes)) {
            return self::Local;
        }

        return str_starts_with($postalCode, '07') ? self::Balearics : self::Peninsula;
    }

    /**
     * Postal code list as typed by staff: "08001-08042, 08901, 0819" → ranges, exact codes and prefixes.
     */
    public static function inList(string $postalCode, ?string $list): bool
    {
        foreach (preg_split('/[\s,;]+/', (string) $list, -1, PREG_SPLIT_NO_EMPTY) as $entry) {
            if (preg_match('/^(\d{5})-(\d{5})$/', $entry, $m)) {
                if ($postalCode >= $m[1] && $postalCode <= $m[2]) {
                    return true;
                }
            } elseif (preg_match('/^\d{2,5}$/', $entry) && str_starts_with($postalCode, $entry)) {
                return true;
            }
        }

        return false;
    }
}
