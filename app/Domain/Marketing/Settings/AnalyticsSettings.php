<?php

namespace App\Domain\Marketing\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Analytics and advertising tags, loaded ONLY after the visitor accepts them in the cookie banner
 * (AEPD "Guía sobre el uso de las cookies", 2023). Edited in "Contenido → Cookies y analítica".
 * With no tag filled in the shop uses only technical cookies and no banner is shown.
 */
class AnalyticsSettings extends Settings
{
    /** Google Analytics 4 measurement id, e.g. G-XXXXXXX. */
    public ?string $ga4_measurement_id;

    /** Meta (Facebook/Instagram) pixel id. */
    public ?string $meta_pixel_id;

    /** Raise it when the cookie policy changes: everybody is asked again. */
    public int $consent_version;

    public static function group(): string
    {
        return 'analytics';
    }

    public function needsConsent(): bool
    {
        return filled($this->ga4_measurement_id) || filled($this->meta_pixel_id);
    }
}
