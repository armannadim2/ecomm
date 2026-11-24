<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Marketing\Enums\CampaignEffect;
use App\Domain\Marketing\Enums\CampaignTheme;
use App\Domain\Marketing\Policies\CampaignPolicy;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Seasonal campaign (Black Friday, Navidad, Rebajas…) that changes the home
 * page between two dates: banner, headline, countdown and a visual effect.
 * Discounts themselves come from the promotions module.
 */
#[Fillable([
    'name', 'theme', 'effect', 'accent_color', 'headline', 'subheadline', 'cta_label', 'cta_url',
    'show_countdown', 'starts_at', 'ends_at', 'is_active', 'priority',
])]
#[UseFactory(CampaignFactory::class)]
#[UsePolicy(CampaignPolicy::class)]
class Campaign extends Model implements HasMedia
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, HasUlids, InteractsWithMedia, LogsActivity, SoftDeletes;

    public const BANNER = 'banner';

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'theme' => CampaignTheme::class,
            'effect' => CampaignEffect::class,
            'show_countdown' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'priority' => 'integer',
        ];
    }

    /** @param Builder<Campaign> $query */
    public function scopeLive(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at)
            ->orderByDesc('priority')
            ->orderByDesc('starts_at');
    }

    public static function current(): ?self
    {
        return self::query()->live()->with('media')->first();
    }

    /** Programada / En curso / Finalizada / Desactivada, for the admin list. */
    public function stateLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Desactivada',
            $this->starts_at->isFuture() => 'Programada',
            $this->ends_at->isPast() => 'Finalizada',
            default => 'En curso',
        };
    }

    public function bannerUrl(string $conversion = 'wide'): ?string
    {
        $media = $this->getFirstMedia(self::BANNER);

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::BANNER)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('wide')->fit(Fit::Max, 1920, 1080)->format('webp');
        $this->addMediaConversion('mobile')->fit(Fit::Max, 800, 1000)->format('webp');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('marketing')
            ->logOnly(['name', 'theme', 'starts_at', 'ends_at', 'is_active', 'headline'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
