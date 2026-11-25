<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductActions;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProductActions::publish()->after(fn () => $this->refreshFormData(['status'])),
            ProductActions::reject()->after(fn () => $this->refreshFormData(['status'])),
            ProductActions::submitForReview()->after(fn () => $this->refreshFormData(['status'])),
            ProductActions::unpublish()->after(fn () => $this->refreshFormData(['status'])),
            ProductActions::schedule()->after(fn () => $this->refreshFormData(['scheduled_publish_at'])),
            ProductActions::cancelSchedule()->after(fn () => $this->refreshFormData(['scheduled_publish_at'])),
            ProductActions::adjustStock(),
            ActionGroup::make([
                ProductActions::duplicate(),
                DeleteAction::make()->label('Enviar a la papelera'),
                RestoreAction::make(),
            ]),
        ];
    }
}
