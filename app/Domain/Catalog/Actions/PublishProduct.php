<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;

/**
 * Makes a product visible in the shop, after checking it is ready to sell.
 */
class PublishProduct
{
    public function handle(Product $product): Product
    {
        $product->loadMissing('variants');

        if ($product->variants->isEmpty()) {
            throw new DomainRuleException('Añade al menos una variante con precio antes de publicar.');
        }

        if ($product->variants->contains(fn ($variant) => $variant->price_cents <= 0)) {
            throw new DomainRuleException('Todas las variantes necesitan un precio mayor que 0 €.');
        }

        if ($product->categories()->doesntExist()) {
            throw new DomainRuleException('Asigna al menos una categoría para que los clientes encuentren el producto.');
        }

        $product->status = ProductStatus::Published;
        $product->published_at ??= now();
        $product->save();

        return $product;
    }
}
