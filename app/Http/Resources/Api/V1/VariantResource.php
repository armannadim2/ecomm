<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class VariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'product_id' => $this->whenLoaded('product', fn () => $this->product->ulid),
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price_cents' => $this->price_cents,
            'compare_at_price_cents' => $this->compare_at_price_cents,
            'currency' => 'EUR',
            'stock' => $this->stock,
            'track_stock' => (bool) $this->track_stock,
            'low_stock_threshold' => $this->low_stock_threshold,
            'weight_grams' => $this->weight_grams,
            'position' => $this->position,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
