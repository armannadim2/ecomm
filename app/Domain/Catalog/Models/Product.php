<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Policies\ProductPolicy;
use App\Domain\Staff\Models\Staff;
use App\Support\HasUniqueSlug;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable([
    'brand_id', 'name', 'slug', 'short_description', 'description',
    'tax_class', 'seo_title', 'seo_description',
])]
#[UseFactory(ProductFactory::class)]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUlids, HasUniqueSlug, InteractsWithMedia, LogsActivity, Searchable, SoftDeletes;

    public const IMAGES = 'images';

    /**
     * `status` and `published_at` are not fillable on purpose:
     * they change only through PublishProduct / UnpublishProduct.
     */
    protected $attributes = [
        'status' => 'draft',
        'tax_class' => 'general',
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'tax_class' => TaxClass::class,
            'published_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Staff, $this> Staff member who uploaded the product. */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by_staff_id');
    }

    /** @return BelongsTo<Staff, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by_staff_id');
    }

    public function isOwnedBy(Staff $staff): bool
    {
        return $this->created_by_staff_id !== null && $this->created_by_staff_id === $staff->id;
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    /** @param Builder<Product> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    /**
     * Fields searched by the storefront. With the "database" driver these are
     * plain LIKE queries; switching to Meilisearch later needs no code change.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'short_description' => $this->short_description,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished() && ! $this->trashed();
    }

    /**
     * URL of the main photo. Falls back to the original while the queued
     * WebP conversion is not generated yet, and to null without photos.
     */
    public function imageUrl(string $conversion = 'medium'): ?string
    {
        $media = $this->getFirstMedia(self::IMAGES);

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Conversions are re-encoded to WebP (this also drops EXIF metadata).
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->fit(Fit::Max, 800, 800)
            ->format('webp');

        $this->addMediaConversion('large')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalog')
            ->logOnly(['brand_id', 'name', 'slug', 'status', 'tax_class', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
