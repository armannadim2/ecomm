<?php

namespace App\Domain\Integrations;

use App\Domain\Integrations\Jobs\SendWebhook;
use App\Domain\Integrations\Models\Webhook;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Queues one delivery per active webhook subscribed to the event. */
class WebhookDispatcher
{
    private const CACHE_KEY = 'webhooks.active';

    /** @param  array<string, mixed>  $data  Same JSON the API returns for the record. */
    public function dispatch(string $event, array $data): void
    {
        foreach ($this->activeWebhooks() as $webhook) {
            if (! in_array($event, $webhook['events'], true)) {
                continue;
            }

            SendWebhook::dispatch($webhook['id'], [
                'id' => (string) Str::ulid(),
                'event' => $event,
                'created_at' => now()->toIso8601String(),
                'data' => $data,
            ]);
        }
    }

    public function hasListeners(string $event): bool
    {
        foreach ($this->activeWebhooks() as $webhook) {
            if (in_array($event, $webhook['events'], true)) {
                return true;
            }
        }

        return false;
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Cached so saving a product does not query the webhooks table every time. @return list<array{id: int, events: list<string>}> */
    private function activeWebhooks(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, fn () => Webhook::query()
            ->where('is_active', true)
            ->get(['id', 'events'])
            ->map(fn (Webhook $w) => ['id' => $w->id, 'events' => $w->events ?? []])
            ->all());
    }
}
