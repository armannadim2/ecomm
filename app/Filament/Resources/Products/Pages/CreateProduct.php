<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** Record who uploaded it (Colaboradores only see their own products). */
    protected function afterCreate(): void
    {
        $this->getRecord()->forceFill(['created_by_staff_id' => auth()->id()])->saveQuietly();
    }

    /** After creating, go to the edit page so the product can be published. */
    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
