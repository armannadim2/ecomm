<?php

namespace App\Filament\Resources\Webhooks\Pages;

use App\Domain\Integrations\Models\Webhook;
use App\Filament\Resources\Webhooks\WebhookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWebhooks extends ManageRecords
{
    protected static string $resource = WebhookResource::class;

    public function getSubheading(): ?string
    {
        return 'La tienda envía un aviso (POST con JSON firmado) a estas direcciones cuando hay pedidos nuevos o cambian productos y stock.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo webhook')
                ->mutateDataUsing(fn (array $data) => $data + ['created_by' => auth()->id()])
                ->after(fn (Webhook $record) => WebhookResource::showSecret($record))
                ->successNotification(null),
        ];
    }
}
