<?php

namespace App\Filament\Imports;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Support\Money;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Product CSV import (CAT-10). One row = one variant, found by its SKU:
 * - new SKU → new variant; its product is found by exact name, or created as a draft;
 * - existing SKU → the variant (and its product's texts) are updated.
 * Stock goes through AdjustStock ("Importación CSV") so every change is logged.
 * "publicar = sí" publishes the product with the usual rules. Prices are euros ("12,50" or "12.50").
 * Errors are reported per row ("Fila 12: …") in the file Filament offers to download.
 */
class ProductImporter extends Importer
{
    protected static ?string $model = ProductVariant::class;

    /** @var Collection<int, Category>|null */
    private ?Collection $categories = null;

    private ?Brand $brand = null;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('sku')->label('Referencia (SKU)')->requiredMapping()
                ->guess(['sku', 'referencia', 'ref'])->rules(['required', 'max:64'])->example('CAM-M-AZUL'),
            ImportColumn::make('product')->label('Producto')->requiredMappingForNewRecordsOnly()
                ->guess(['producto', 'product', 'nombre'])->rules(['required_without:sku', 'max:200'])
                ->fillRecordUsing(fn () => null)->example('Camiseta básica'),
            ImportColumn::make('name')->label('Variante')->guess(['variante', 'variant'])
                ->rules(['nullable', 'max:120'])->example('M / Azul'),
            ImportColumn::make('price_cents')->label('Precio (€, IVA incluido)')->requiredMappingForNewRecordsOnly()
                ->guess(['precio', 'price', 'pvp'])
                ->castStateUsing(fn ($state) => self::cents($state))
                ->rules(['required', 'integer', 'min:1'])->example('12,50'),
            ImportColumn::make('compare_at_price_cents')->label('Precio anterior (€)')->guess(['precio_anterior', 'compare_at_price'])
                ->castStateUsing(fn ($state) => self::cents($state))
                ->rules(['nullable', 'integer', 'min:1'])->example(''),
            ImportColumn::make('stock')->label('Stock')->integer()->guess(['stock', 'unidades', 'cantidad'])
                ->rules(['nullable', 'integer', 'min:0', 'max:1000000'])
                ->fillRecordUsing(fn () => null)->example('10'),
            ImportColumn::make('barcode')->label('Código de barras')->guess(['ean', 'barcode', 'codigo_barras'])
                ->rules(['nullable', 'max:64'])->example('8410000000017'),
            ImportColumn::make('weight_grams')->label('Peso (g)')->integer()->guess(['peso', 'weight'])
                ->rules(['nullable', 'integer', 'min:0'])->example('250'),
            ImportColumn::make('categories')->label('Categorías')->guess(['categorias', 'categoria', 'categories'])
                ->helperText('Nombres separados por «|», por ejemplo: Cocina|Menaje')
                ->fillRecordUsing(fn () => null)->example('Camisetas'),
            ImportColumn::make('brand')->label('Marca')->guess(['marca', 'brand'])
                ->fillRecordUsing(fn () => null)->example(''),
            ImportColumn::make('short_description')->label('Descripción corta')->guess(['descripcion_corta', 'short_description'])
                ->rules(['nullable', 'max:500'])->fillRecordUsing(fn () => null)->example(''),
            ImportColumn::make('description')->label('Descripción')->guess(['descripcion', 'description'])
                ->rules(['nullable', 'max:20000'])->fillRecordUsing(fn () => null)->example(''),
            ImportColumn::make('tax_class')->label('IVA')->guess(['iva', 'tax_class'])
                ->helperText('general (21 %), reduced (10 %) o super_reduced (4 %)')
                ->rules(['nullable', 'in:general,reduced,super_reduced,21,10,4'])
                ->fillRecordUsing(fn () => null)->example('general'),
            ImportColumn::make('publish')->label('Publicar')->boolean()->guess(['publicar', 'publish'])
                ->helperText('«sí» para publicarlo si está listo para vender.')
                ->fillRecordUsing(fn () => null)->example('no'),
        ];
    }

    public function resolveRecord(): ?ProductVariant
    {
        return ProductVariant::query()->firstOrNew(['sku' => trim((string) $this->data['sku'])]);
    }

    /** Checks everything that could fail before anything is written. */
    protected function afterValidate(): void
    {
        if (! $this->record->exists && blank($this->data['product'] ?? null)) {
            throw new RowImportFailedException('Falta el nombre del producto para la nueva referencia '.$this->data['sku'].'.');
        }

        $this->categories = null;
        if (filled($this->data['categories'] ?? null)) {
            $names = array_values(array_filter(array_map('trim', explode('|', (string) $this->data['categories']))));
            $this->categories = Category::query()->whereIn('name', $names)->orWhereIn('slug', $names)->get();
            $missing = array_filter($names, fn ($n) => ! $this->categories->contains(fn ($c) => mb_strtolower($c->name) === mb_strtolower($n) || $c->slug === $n));
            if ($missing !== []) {
                throw new RowImportFailedException('No existe la categoría «'.implode('», «', $missing).'». Créala antes en Catálogo → Categorías.');
            }
        }

        $this->brand = filled($this->data['brand'] ?? null)
            ? Brand::query()->firstOrCreate(['name' => trim((string) $this->data['brand'])])
            : null;
    }

    protected function beforeSave(): void
    {
        $product = $this->record->exists
            ? $this->record->product
            : (Product::query()->where('name', trim((string) $this->data['product']))->first()
                ?? new Product(['name' => trim((string) $this->data['product'])]));

        foreach (['short_description', 'description'] as $field) {
            if (filled($this->data[$field] ?? null)) {
                $product->{$field} = $this->data[$field];
            }
        }
        if (filled($this->data['tax_class'] ?? null)) {
            $product->tax_class = match ((string) $this->data['tax_class']) {
                '21' => TaxClass::General, '10' => TaxClass::Reduced, '4' => TaxClass::SuperReduced,
                default => TaxClass::from((string) $this->data['tax_class']),
            };
        }
        if ($this->brand) {
            $product->brand_id = $this->brand->id;
        }
        $product->save();

        if ($this->categories) {
            $product->categories()->syncWithoutDetaching($this->categories->pluck('id'));
        }

        $this->record->product()->associate($product);

        if (! $this->record->exists) {
            $this->record->stock = (int) ($this->data['stock'] ?? 0);
            $this->record->position = (int) $product->variants()->max('position') + 1;
        }
    }

    protected function afterSave(): void
    {
        $variant = $this->record;

        // Existing variant: stock changes are logged like any other adjustment.
        if ($variant->wasRecentlyCreated === false && filled($this->data['stock'] ?? null)) {
            $change = (int) $this->data['stock'] - $variant->stock;
            if ($change !== 0) {
                app(AdjustStock::class)->handle($variant, $change, 'Importación CSV');
            }
        }

        if (($this->data['publish'] ?? false) === true && ! $variant->product->isPublished()) {
            try {
                app(PublishProduct::class)->handle($variant->product->fresh());
            } catch (DomainRuleException $e) {
                throw new RowImportFailedException('Guardado como borrador, pero no se ha publicado: '.$e->getMessage());
            }
        }
    }

    private static function cents(mixed $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        $value = str_replace([' ', '€'], '', (string) $state);
        // "1.234,56" or "12,50" (Spanish) → "1234.56" / "12.50"
        if (str_contains($value, ',')) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        }

        return is_numeric($value) ? Money::toCents($value) : -1; // -1 fails the "min:1" rule
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Importación de productos terminada: '.Number::format($import->successful_rows).' '.Str::plural('fila', $import->successful_rows).' correctas.';

        if ($failed = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failed).' con errores: descarga el archivo para ver el motivo de cada una.';
        }

        return $body;
    }
}
