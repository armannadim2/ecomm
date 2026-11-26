<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Redsys (cards + Bizum). Off until the bank gives the terminal data.
        $this->migrator->add('payments.redsys_card_enabled', false);
        $this->migrator->add('payments.redsys_bizum_enabled', false);
        $this->migrator->add('payments.redsys_test_mode', true);
        $this->migrator->add('payments.redsys_merchant_code', null);
        $this->migrator->add('payments.redsys_terminal', '1');
        $this->migrator->addEncrypted('payments.redsys_secret_key', null);

        // PayPal (Orders API v2).
        $this->migrator->add('payments.paypal_enabled', false);
        $this->migrator->add('payments.paypal_sandbox', true);
        $this->migrator->add('payments.paypal_client_id', null);
        $this->migrator->addEncrypted('payments.paypal_client_secret', null);
        $this->migrator->add('payments.paypal_webhook_id', null);

        // Stock of an unpaid online order stays reserved this long.
        $this->migrator->add('payments.reservation_minutes', 30);
    }
};
