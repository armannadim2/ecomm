<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // The shop's real details (the owner can change them in "Datos de la tienda").
        $this->migrator->add('store.store_name', 'Tot & Més');
        $this->migrator->add('store.tagline', null);
        $this->migrator->add('store.legal_name', null);
        $this->migrator->add('store.nif', null);
        $this->migrator->add('store.address_line', 'Avinguda Meridiana 507, Local 2');
        $this->migrator->add('store.postal_code', '08016');
        $this->migrator->add('store.city', 'Barcelona');
        $this->migrator->add('store.province', 'Barcelona');
        $this->migrator->add('store.country', 'ES');
        $this->migrator->add('store.email', 'hello@totymes.com');
        $this->migrator->add('store.phone', '657 21 58 53');
        $this->migrator->add('store.registry_info', null);
        $this->migrator->add('store.logo_path', null);
    }
};
