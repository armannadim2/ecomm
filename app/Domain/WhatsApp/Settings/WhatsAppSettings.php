<?php

namespace App\Domain\WhatsApp\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * WhatsApp customer service: Meta WhatsApp Cloud API credentials and the AI assistant.
 * Edited in "Atención al cliente → Ajustes de WhatsApp". Secrets are stored encrypted.
 */
class WhatsAppSettings extends Settings
{
    /** Receive and answer WhatsApp messages through the Cloud API. */
    public bool $enabled;

    /** Let the AI assistant answer; when off, every conversation waits for a person. */
    public bool $agent_enabled;

    /** Meta "Phone number ID" of the shop's WhatsApp Business number. */
    public ?string $phone_number_id;

    /** Meta permanent access token (System user token). */
    public ?string $access_token;

    /** Meta app secret: checks that webhook calls really come from Meta. */
    public ?string $app_secret;

    /** Text typed in Meta's webhook settings to confirm the URL. */
    public ?string $verify_token;

    /** Anthropic API key for the assistant. */
    public ?string $anthropic_api_key;

    /** Claude model for the assistant. */
    public string $model;

    /** Extra instructions from the shop for the assistant (tone, offers…). */
    public ?string $instructions;

    /** Sent to the customer when the assistant passes the conversation to a person. */
    public string $handoff_message;

    public static function group(): string
    {
        return 'whatsapp';
    }

    public static function encrypted(): array
    {
        return ['access_token', 'app_secret', 'anthropic_api_key'];
    }

    public function isConfigured(): bool
    {
        return $this->enabled && filled($this->phone_number_id) && filled($this->access_token) && filled($this->app_secret);
    }

    public function agentReady(): bool
    {
        return $this->isConfigured() && $this->agent_enabled && filled($this->anthropic_api_key);
    }
}
