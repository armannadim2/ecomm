<?php

namespace App\Domain\Payments\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Online payment gateways. Edited in "Pedidos → Pagos online". Secrets are stored encrypted.
 * A method is offered to customers only when it is switched on AND its credentials are filled in.
 */
class PaymentSettings extends Settings
{
    public bool $redsys_card_enabled;

    public bool $redsys_bizum_enabled;

    /** Use the Redsys test environment (sis-t.redsys.es) with the test terminal. */
    public bool $redsys_test_mode;

    /** "Número de comercio" (FUC), 9 digits, from the bank. */
    public ?string $redsys_merchant_code;

    public string $redsys_terminal;

    /** "Clave secreta de encriptación (SHA-256)" from the Redsys admin panel. */
    public ?string $redsys_secret_key;

    public bool $paypal_enabled;

    public bool $paypal_sandbox;

    public ?string $paypal_client_id;

    public ?string $paypal_client_secret;

    /** Id of the webhook created in the PayPal developer dashboard (used to verify webhook calls). */
    public ?string $paypal_webhook_id;

    public int $reservation_minutes;

    public static function group(): string
    {
        return 'payments';
    }

    public static function encrypted(): array
    {
        return ['redsys_secret_key', 'paypal_client_secret'];
    }

    public function redsysConfigured(): bool
    {
        return filled($this->redsys_merchant_code) && filled($this->redsys_terminal) && filled($this->redsys_secret_key);
    }

    public function paypalConfigured(): bool
    {
        return filled($this->paypal_client_id) && filled($this->paypal_client_secret);
    }

    public function cardReady(): bool
    {
        return $this->redsys_card_enabled && $this->redsysConfigured();
    }

    public function bizumReady(): bool
    {
        return $this->redsys_bizum_enabled && $this->redsysConfigured();
    }

    public function paypalReady(): bool
    {
        return $this->paypal_enabled && $this->paypalConfigured();
    }

    public function anyOnlineReady(): bool
    {
        return $this->cardReady() || $this->bizumReady() || $this->paypalReady();
    }
}
