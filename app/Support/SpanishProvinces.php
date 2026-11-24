<?php

namespace App\Support;

/**
 * Spanish provinces by the first two digits of the postal code.
 * Lets forms fill the province automatically, and lets shipping rules
 * tell Peninsula/Balearics apart from Canarias, Ceuta and Melilla.
 */
final class SpanishProvinces
{
    public const BY_PREFIX = [
        '01' => 'Álava', '02' => 'Albacete', '03' => 'Alicante', '04' => 'Almería', '05' => 'Ávila',
        '06' => 'Badajoz', '07' => 'Illes Balears', '08' => 'Barcelona', '09' => 'Burgos', '10' => 'Cáceres',
        '11' => 'Cádiz', '12' => 'Castellón', '13' => 'Ciudad Real', '14' => 'Córdoba', '15' => 'A Coruña',
        '16' => 'Cuenca', '17' => 'Girona', '18' => 'Granada', '19' => 'Guadalajara', '20' => 'Gipuzkoa',
        '21' => 'Huelva', '22' => 'Huesca', '23' => 'Jaén', '24' => 'León', '25' => 'Lleida',
        '26' => 'La Rioja', '27' => 'Lugo', '28' => 'Madrid', '29' => 'Málaga', '30' => 'Murcia',
        '31' => 'Navarra', '32' => 'Ourense', '33' => 'Asturias', '34' => 'Palencia', '35' => 'Las Palmas',
        '36' => 'Pontevedra', '37' => 'Salamanca', '38' => 'Santa Cruz de Tenerife', '39' => 'Cantabria',
        '40' => 'Segovia', '41' => 'Sevilla', '42' => 'Soria', '43' => 'Tarragona', '44' => 'Teruel',
        '45' => 'Toledo', '46' => 'Valencia', '47' => 'Valladolid', '48' => 'Bizkaia', '49' => 'Zamora',
        '50' => 'Zaragoza', '51' => 'Ceuta', '52' => 'Melilla',
    ];

    /** Canarias, Ceuta and Melilla: outside the IVA area, not served at launch. */
    public const NON_IVA_PREFIXES = ['35', '38', '51', '52'];

    public static function fromPostalCode(?string $postalCode): ?string
    {
        if ($postalCode === null || ! preg_match('/^\d{5}$/', $postalCode)) {
            return null;
        }

        return self::BY_PREFIX[substr($postalCode, 0, 2)] ?? null;
    }

    public static function isShippable(string $postalCode): bool
    {
        return self::fromPostalCode($postalCode) !== null
            && ! in_array(substr($postalCode, 0, 2), self::NON_IVA_PREFIXES, true);
    }
}
