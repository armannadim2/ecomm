<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('analytics.ga4_measurement_id', null);
        $this->migrator->add('analytics.meta_pixel_id', null);
        $this->migrator->add('analytics.consent_version', 1);
    }
};
