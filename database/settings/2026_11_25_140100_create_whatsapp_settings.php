<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('whatsapp.enabled', false);
        $this->migrator->add('whatsapp.agent_enabled', true);
        $this->migrator->add('whatsapp.phone_number_id', null);
        $this->migrator->addEncrypted('whatsapp.access_token', null);
        $this->migrator->addEncrypted('whatsapp.app_secret', null);
        $this->migrator->add('whatsapp.verify_token', null);
        $this->migrator->addEncrypted('whatsapp.anthropic_api_key', null);
        $this->migrator->add('whatsapp.model', 'claude-opus-5-5');
        $this->migrator->add('whatsapp.instructions', null);
        $this->migrator->add('whatsapp.handoff_message', 'Gracias por tu paciencia. Una persona del equipo de Tot & Més te responderá en cuanto pueda, en horario de tienda.');
    }
};
