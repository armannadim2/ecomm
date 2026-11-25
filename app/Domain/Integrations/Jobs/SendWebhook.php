<?php

namespace App\Domain\Integrations\Jobs;

use App\Domain\Integrations\Models\Webhook;
use App\Domain\Integrations\SafeUrl;
use App\Domain\Integrations\WebhookDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * POSTs one event to one webhook URL, signed with the webhook secret.
 * Retries with growing pauses; after Webhook::MAX_FAILURES failures in a row the webhook is switched off.
 */
class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @param  array{id: string, event: string, created_at: string, data: array<string, mixed>}  $payload */
    public function __construct(public int $webhookId, public array $payload) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        $webhook = Webhook::query()->find($this->webhookId);
        if ($webhook === null || ! $webhook->is_active) {
            return;
        }

        if ($problem = SafeUrl::check($webhook->url)) {
            $this->recordFailure($webhook, 0, $problem);

            return;
        }

        $body = json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        try {
            $response = Http::timeout(10)
                ->withoutRedirecting()
                ->withUserAgent('TotMes-Webhooks/1.0')
                ->withHeaders([
                    'X-TotMes-Event' => $this->payload['event'],
                    'X-TotMes-Delivery' => $this->payload['id'],
                    'X-TotMes-Signature' => $webhook->sign($body),
                ])
                ->withBody($body, 'application/json')
                ->post($webhook->url);
        } catch (Throwable $e) {
            $this->recordFailure($webhook, 0, Str::limit($e->getMessage(), 500));

            throw new RuntimeException('Webhook delivery failed: '.$e->getMessage(), previous: $e);
        }

        if ($response->successful()) {
            $webhook->forceFill([
                'consecutive_failures' => 0,
                'last_status' => $response->status(),
                'last_error' => null,
                'last_delivered_at' => now(),
            ])->saveQuietly();

            return;
        }

        $this->recordFailure($webhook, $response->status(), 'Respuesta HTTP '.$response->status());

        throw new RuntimeException('Webhook answered HTTP '.$response->status());
    }

    private function recordFailure(Webhook $webhook, int $status, string $error): void
    {
        $failures = $webhook->consecutive_failures + 1;

        $webhook->forceFill([
            'consecutive_failures' => $failures,
            'last_status' => $status ?: null,
            'last_error' => $error,
            'is_active' => $failures < Webhook::MAX_FAILURES,
        ])->saveQuietly();

        if ($failures >= Webhook::MAX_FAILURES) {
            WebhookDispatcher::forgetCache();
        }
    }
}
