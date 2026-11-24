<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Default brand (Stitch design "Tot & Més"). The owner can rename it in "Datos de la tienda".
        $this->migrator->add('store.store_name', 'Tot & Més');
        $this->migrator->add('store.tagline', null);
        $this->migrator->add('store.legal_name', null);
        $this->migrator->add('store.nif', null);
        $this->migrator->add('store.address_line', null);
        $this->migrator->add('store.postal_code', null);
        $this->migrator->add('store.city', null);
        $this->migrator->add('store.province', null);
        $this->migrator->add('store.country', 'ES');
        $this->migrator->add('store.email', null);
        $this->migrator->add('store.phone', null);
        $this->migrator->add('store.registry_info', null);
        $this->migrator->add('store.logo_path', null);
    }
};
