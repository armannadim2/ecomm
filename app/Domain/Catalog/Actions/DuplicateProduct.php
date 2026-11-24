<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Copies a product (with variants and categories) as a new draft.
 * Stock is not copied; SKUs get a "-COPIA" suffix to stay unique.
 */
class DuplicateProduct
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            $copy = $product->replicate(['ulid', 'slug', 'status', 'published_at']);
            $copy->name = $product->name.' (copia)';
            $copy->slug = null;
            $copy->status = ProductStatus::Draft;
            $copy->save();

            $copy->categories()->sync($product->categories()->pluck('categories.id'));

            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate(['ulid', 'product_id', 'sku', 'stock']);
                $newVariant->sku = $this->uniqueSku($variant->sku);
                $newVariant->stock = 0;
                $copy->variants()->save($newVariant);
            }

            return $copy;
        });
    }

    private function uniqueSku(string $sku): string
    {
        $candidate = "{$sku}-COPIA";
        $i = 2;

        while (ProductVariant::query()->where('sku', $candidate)->exists()) {
            $candidate = "{$sku}-COPIA{$i}";
            $i++;
        }

        return $candidate;
    }
}
