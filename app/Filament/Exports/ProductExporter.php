<?php

namespace App\Filament\Exports;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\ProductVariant;
use App\Support\Money;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

/**
 * Product CSV export (CAT-10). One row per variant. The headers are the ones the importer
 * recognises, so staff can export, edit in Excel and import the file back.
 */
class ProductExporter extends Exporter
{
    protected static ?string $model = ProductVariant::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('sku')->label('sku'),
            ExportColumn::make('product.name')->label('producto'),
            ExportColumn::make('name')->label('variante'),
            ExportColumn::make('price_cents')->label('precio')->formatStateUsing(fn ($state) => self::euros($state)),
            ExportColumn::make('compare_at_price_cents')->label('precio_anterior')->formatStateUsing(fn ($state) => self::euros($state)),
            ExportColumn::make('stock')->label('stock'),
            ExportColumn::make('barcode')->label('ean'),
            ExportColumn::make('weight_grams')->label('peso'),
            ExportColumn::make('categories')->label('categorias')
                ->state(fn (ProductVariant $record) => $record->product?->categories->pluck('name')->implode('|')),
            ExportColumn::make('product.brand.name')->label('marca'),
            ExportColumn::make('product.short_description')->label('descripcion_corta'),
            ExportColumn::make('product.description')->label('descripcion')->enabledByDefault(false),
            ExportColumn::make('product.tax_class')->label('iva')->formatStateUsing(fn ($state) => $state?->value),
            ExportColumn::make('publish')->label('publicar')
                ->state(fn (ProductVariant $record) => $record->product?->status === ProductStatus::Published ? 'sí' : 'no'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->whereHas('product')->with(['product.categories', 'product.brand']);
    }

    /** Excel in Spain reads "12,50" as a number. */
    private static function euros(?int $cents): ?string
    {
        return $cents === null ? null : str_replace('.', ',', Money::toEuros($cents));
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Exportación de productos lista: '.Number::format($export->successful_rows).' filas.';

        if ($failed = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' no se pudieron exportar.';
        }

        return $body;
    }
}
