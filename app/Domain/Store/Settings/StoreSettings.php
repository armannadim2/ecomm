<?php

namespace App\Domain\Store\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Shop identity and legal data (LSSI requires it on the website).
 * Edited by the owner in "Datos de la tienda".
 */
class StoreSettings extends Settings
{
    public string $store_name;

    public ?string $tagline;

    public ?string $legal_name;

    public ?string $nif;

    public ?string $address_line;

    public ?string $postal_code;

    public ?string $city;

    public ?string $province;

    public string $country;

    public ?string $email;

    public ?string $phone;

    /** Mercantile registry data (Registro Mercantil), if the business is a company. */
    public ?string $registry_info;

    public ?string $logo_path;

    public static function group(): string
    {
        return 'store';
    }

    public function hasLegalData(): bool
    {
        return filled($this->legal_name) && filled($this->nif) && filled($this->address_line)
            && filled($this->postal_code) && filled($this->city) && filled($this->email);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line,
            trim(($this->postal_code ?? '').' '.($this->city ?? '')),
            $this->province,
            'España',
        ])->filter()->implode(', ');
    }
}
