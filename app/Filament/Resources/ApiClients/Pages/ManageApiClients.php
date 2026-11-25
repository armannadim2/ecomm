<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Domain\Integrations\Models\ApiClient;
use App\Filament\Resources\ApiClients\ApiClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageApiClients extends ManageRecords
{
    protected static string $resource = ApiClientResource::class;

    public function getSubheading(): ?string
    {
        return 'Permiten que otros sistemas (TPV, ERP, marketplaces) lean o cambien productos, stock y pedidos con la API. Guía técnica: docs/API.md.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva clave API')
                ->schema([...ApiClientResource::detailFields(), ...ApiClientResource::scopeFields()])
                ->using(function (array $data): ApiClient {
                    $client = ApiClient::query()->create([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                    ApiClientResource::showKey($client->issueKey($data['scopes'])->plainTextToken);

                    return $client;
                })
                ->successNotification(null),
        ];
    }
}
