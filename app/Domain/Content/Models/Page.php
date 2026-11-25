<?php

namespace App\Domain\Content\Models;

use App\Domain\Content\Blocks\Block;
use App\Domain\Content\Policies\PagePolicy;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Store\Settings\StoreSettings;
use App\Support\HasUniqueSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Content page (legal texts, "Sobre nosotros", FAQ...) and the home page (slug "inicio").
 *
 * Pages can be built with page-builder blocks ($blocks, see App\Domain\Content\Blocks) shown under the body.
 *
 * Bodies may contain placeholders like [nombre_legal] or [nif] that are
 * filled from the store settings when the page is shown.
 */
#[Fillable(['title', 'slug', 'body', 'blocks', 'draft_blocks', 'draft_saved_at', 'type', 'is_published', 'show_in_footer', 'position', 'seo_description'])]
#[UsePolicy(PagePolicy::class)]
class Page extends Model
{
    use HasUniqueSlug, LogsActivity, SoftDeletes;

    public const TYPE_LEGAL = 'legal';

    public const TYPE_INFO = 'info';

    /** Reserved page that holds the home page blocks. */
    public const HOME_SLUG = 'inicio';

    /** Placeholder => StoreSettings property. */
    public const PLACEHOLDERS = [
        '[nombre_tienda]' => 'store_name',
        '[nombre_legal]' => 'legal_name',
        '[nif]' => 'nif',
        '[direccion]' => 'fullAddress',
        '[email]' => 'email',
        '[telefono]' => 'phone',
        '[registro_mercantil]' => 'registry_info',
    ];

    public function slugSource(): string
    {
        return (string) $this->title;
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
            'position' => 'integer',
            'blocks' => 'array',
            'draft_blocks' => 'array',
            'draft_saved_at' => 'datetime',
        ];
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME_SLUG;
    }

    /** @param Builder<Page> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Safe HTML for the storefront, with store data filled in. */
    public function renderedBody(StoreSettings $settings): string
    {
        $replacements = [];

        foreach (self::PLACEHOLDERS as $token => $property) {
            $value = $property === 'fullAddress' ? $settings->fullAddress() : $settings->{$property};
            $replacements[$token] = e($value ?: '—');
        }

        // Shop web address and opening hours (the latter from Envíos y pagos, in the visitor's language).
        $replacements['[web]'] = e(parse_url((string) config('app.url'), PHP_URL_HOST) ?: (string) config('app.url'));
        $hours = new Block('hours', ['h' => app(CheckoutSettings::class)->opening_hours]);
        $replacements['[horario]'] = e($hours->text('h') ?: '—');

        return strtr(Str::sanitizeHtml((string) $this->body), $replacements);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('content')
            ->logOnly(['title', 'slug', 'is_published', 'body', 'blocks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
