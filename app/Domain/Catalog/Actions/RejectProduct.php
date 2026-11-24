<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Sends a submitted product back to its uploader with a note on what to fix.
 */
class RejectProduct
{
    public function handle(Product $product, Staff $reviewer, string $note): Product
    {
        if ($product->status !== ProductStatus::PendingReview) {
            throw new DomainRuleException('Solo se pueden devolver productos pendientes de revisión.');
        }

        if (trim($note) === '') {
            throw new DomainRuleException('Explica qué hay que cambiar para que el colaborador pueda corregirlo.');
        }

        $product->status = ProductStatus::Draft;
        $product->review_note = $note;
        $product->reviewed_by_staff_id = $reviewer->id;
        $product->save();
        $product->loadMissing('createdBy');

        if ($product->createdBy !== null) {
            Notification::make()
                ->warning()
                ->title("«{$product->name}» necesita cambios")
                ->body($note)
                ->actions([
                    Action::make('edit')->label('Corregir')->button()
                        ->url(ProductResource::getUrl('edit', ['record' => $product], panel: 'admin')),
                ])
                ->sendToDatabase($product->createdBy);
        }

        return $product;
    }
}
