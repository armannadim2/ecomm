<?php

namespace App\Domain\Integrations\Models;

use App\Domain\Integrations\Policies\IntegrationPolicy;
use App\Domain\Integrations\WebhookDispatcher;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A URL the shop calls (POST, JSON) when something changes. Each request carries
 * X-TotMes-Signature: sha256=HMAC(body, secret) so the receiver can check it came from the shop.
 * After 10 failed deliveries in a row it is switched off.
 */
#[UsePolicy(IntegrationPolicy::class)]
#[Fillable(['name', 'url', 'events', 'is_active', 'created_by'])]
#[Hidden(['secret'])]
class Webhook extends Model
{
    use HasUlids, LogsActivity;

    public const MAX_FAILURES = 10;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected static function booted(): void
    {
        static::creating(function (Webhook $webhook) {
            $webhook->secret ??= 'whsec_'.Str::random(40);
        });
        static::saved(fn () => WebhookDispatcher::forgetCache());
        static::deleted(fn () => WebhookDispatcher::forgetCache());
    }

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'secret' => 'encrypted',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /** @param  Builder<Webhook>  $query */
    public function scopeListeningTo(Builder $query, string $event): void
    {
        $query->where('is_active', true)->whereJsonContains('events', $event);
    }

    public function sign(string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $body, $this->secret);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('integrations')->logOnly(['name', 'url', 'events', 'is_active'])->logOnlyDirty();
    }
}
