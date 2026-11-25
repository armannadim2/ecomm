<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('checkout.pickup_enabled', true);
        // Home delivery needs online payment, which is not connected yet.
        $this->migrator->add('checkout.delivery_enabled', false);
        $this->migrator->add('checkout.delivery_cost_cents', 395);
        $this->migrator->add('checkout.free_delivery_threshold_cents', 3500);
        $this->migrator->add('checkout.pay_in_store_enabled', true);
        $this->migrator->add('checkout.pickup_note', [
            'es' => 'Listo en 2 horas en horario de tienda · Mostrador prioritario sin colas',
            'ca' => 'A punt en 2 hores en horari de botiga · Mostrador prioritari sense cues',
            'en' => 'Ready in 2 hours during opening hours · Priority counter, no queues',
        ]);
        $this->migrator->add('checkout.opening_hours', [
            'es' => 'Horario ininterrumpido: 10:00 - 21:00 h (lunes a domingo)',
            'ca' => 'Horari ininterromput: 10:00 - 21:00 h (dilluns a diumenge)',
            'en' => 'Open all day: 10:00 - 21:00 (Monday to Sunday)',
        ]);
    }
};
