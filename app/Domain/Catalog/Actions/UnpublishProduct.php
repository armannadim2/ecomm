<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;

/**
 * Hides a product from the shop without deleting it.
 * Draft = being prepared; Archived = no longer sold but kept for history.
 */
class UnpublishProduct
{
    public function handle(Product $product, bool $archive = false): Product
    {
        $product->status = $archive ? ProductStatus::Archived : ProductStatus::Draft;
        $product->save();

        return $product;
    }
}
