<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use Filament\Notifications\Notification;

/**
 * Makes a product visible in the shop, after checking it is ready to sell.
 * When it was waiting for review, the uploader is told it is now live.
 */
class PublishProduct
{
    public function handle(Product $product, ?Staff $reviewer = null): Product
    {
        self::assertReadyToSell($product);

        $wasPending = $product->status === ProductStatus::PendingReview;

        $product->status = ProductStatus::Published;
        $product->published_at ??= now();
        $product->review_note = null;

        if ($reviewer !== null) {
            $product->reviewed_by_staff_id = $reviewer->id;
        }

        $product->save();
        $product->loadMissing('createdBy');

        if ($wasPending && $product->createdBy !== null && ! $product->createdBy->is($reviewer)) {
            Notification::make()
                ->success()
                ->title("«{$product->name}» ya está publicado")
                ->body('Tu producto ya es visible en la tienda.')
                ->sendToDatabase($product->createdBy);
        }

        return $product;
    }

    /** Shared readiness rules (also used when submitting for review). */
    public static function assertReadyToSell(Product $product): void
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
    }
}
