<?php

namespace App\Domain\Catalog\Models;

use App\Support\Money;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A purchasable unit of a product (e.g. "Talla M / Azul").
 * Every product has at least one variant; simple products have exactly one.
 *
 * `stock` is set once when the variant is created. After that it changes
 * only through AdjustStock, so every change is logged with a reason.
 */
#[Fillable([
    'name', 'sku', 'barcode', 'price_cents', 'compare_at_price_cents', 'stock',
    'track_stock', 'low_stock_threshold', 'weight_grams', 'position',
])]
#[UseFactory(ProductVariantFactory::class)]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, HasUlids, LogsActivity;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'compare_at_price_cents' => 'integer',
            'track_stock' => 'boolean',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->stock <= $this->low_stock_threshold;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price_cents);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalog')
            ->logOnly(['name', 'sku', 'price_cents', 'compare_at_price_cents', 'stock', 'track_stock'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
