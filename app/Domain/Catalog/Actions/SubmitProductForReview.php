<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

/**
 * A Colaborador sends a product for review. Everyone who can publish gets
 * a notification in the admin bell with a link to the product.
 */
class SubmitProductForReview
{
    public function handle(Product $product): Product
    {
        PublishProduct::assertReadyToSell($product);

        $product->status = ProductStatus::PendingReview;
        $product->submitted_at = now();
        $product->review_note = null;
        $product->save();

        $reviewers = $this->reviewers();

        if ($reviewers->isNotEmpty()) {
            Notification::make()
                ->info()
                ->title('Producto pendiente de revisión')
                ->body("«{$product->name}» espera tu aprobación.")
                ->actions([
                    Action::make('review')->label('Revisar')->button()
                        ->url(ProductResource::getUrl('edit', ['record' => $product], panel: 'admin')),
                ])
                ->sendToDatabase($reviewers);
        }

        return $product;
    }

    /** @return Collection<int, Staff> */
    private function reviewers()
    {
        $permission = Permission::findByName('products.publish', Staff::GUARD);

        return Staff::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->role(Staff::OWNER_ROLE)
                ->orWhereHas('roles.permissions', fn ($p) => $p->whereKey($permission->id))
                ->orWhereHas('permissions', fn ($p) => $p->whereKey($permission->id)))
            ->get();
    }
}
