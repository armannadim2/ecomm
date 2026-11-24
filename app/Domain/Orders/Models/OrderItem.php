<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id', 'product_variant_id', 'product_name', 'variant_name', 'sku',
    'unit_price_cents', 'compare_at_price_cents', 'quantity', 'tax_rate', 'line_total_cents',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'compare_at_price_cents' => 'integer',
            'quantity' => 'integer',
            'tax_rate' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
