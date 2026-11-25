<?php

namespace App\Domain\WhatsApp;

use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Sends messages through Meta's WhatsApp Cloud API (Graph API).
 * Docs: developers.facebook.com/docs/whatsapp/cloud-api/messages/text-messages
 */
class WhatsAppClient
{
    public const GRAPH_VERSION = 'v23.0';

    /** WhatsApp text messages are limited to 4096 characters. */
    public const MAX_LENGTH = 4096;

    public function __construct(private readonly WhatsAppSettings $settings) {}

    /** Sends a text message and returns Meta's message id. */
    public function sendText(string $waId, string $body): string
    {
        if (! $this->settings->isConfigured()) {
            throw new DomainRuleException('WhatsApp no está configurado: revisa Atención al cliente → Ajustes de WhatsApp.');
        }

        try {
            $response = Http::withToken((string) $this->settings->access_token)
                ->timeout(15)
                ->acceptJson()
                ->post('https://graph.facebook.com/'.self::GRAPH_VERSION.'/'.$this->settings->phone_number_id.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $waId,
                    'type' => 'text',
                    'text' => ['preview_url' => true, 'body' => Str::limit($body, self::MAX_LENGTH - 3)],
                ]);
        } catch (ConnectionException $e) {
            throw new DomainRuleException('No se ha podido conectar con WhatsApp. Inténtalo de nuevo en unos minutos.', previous: $e);
        }

        if ($response->failed()) {
            $error = $response->json('error.message') ?? 'HTTP '.$response->status();

            throw new DomainRuleException('WhatsApp no ha aceptado el mensaje: '.$error);
        }

        return (string) $response->json('messages.0.id');
    }

    /** Meta signs each webhook call: X-Hub-Signature-256 = sha256=HMAC(raw body, app secret). */
    public function validSignature(string $rawBody, ?string $header): bool
    {
        $secret = (string) $this->settings->app_secret;

        return $secret !== '' && is_string($header)
            && hash_equals('sha256='.hash_hmac('sha256', $rawBody, $secret), $header);
    }
}
