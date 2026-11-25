<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        $staff = auth()->user();

        return [
            CreateAction::make()->label('Nuevo producto'),
            // CAT-10: bulk import/export as CSV (one row per variant). Runs in the queue.
            ImportAction::make()
                ->label('Importar CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->importer(ProductImporter::class)
                ->chunkSize(100)
                ->visible((bool) ($staff?->can('products.create') && $staff->can('products.update') && $staff->can('products.stock'))),
            ExportAction::make()
                ->label('Exportar CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->exporter(ProductExporter::class)
                ->fileDisk('local') // product files never on the public disk
                ->visible((bool) $staff?->can('products.view')),
        ];
    }
}
