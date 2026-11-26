<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('checkout.balearics_cost_cents', 795);
        $this->migrator->add('checkout.balearics_free_delivery', false);
        $this->migrator->add('checkout.weight_rates', []);
        $this->migrator->add('checkout.local_delivery_enabled', false);
        $this->migrator->add('checkout.local_delivery_postcodes', '08001-08042');
        $this->migrator->add('checkout.local_delivery_cost_cents', 295);
        $this->migrator->add('checkout.local_delivery_note', [
            'es' => 'Te lo llevamos nosotros en 24 h (Barcelona ciudad)',
            'ca' => 'Te\'l portem nosaltres en 24 h (Barcelona ciutat)',
            'en' => 'We deliver it ourselves within 24 h (Barcelona city)',
        ]);
    }
};
