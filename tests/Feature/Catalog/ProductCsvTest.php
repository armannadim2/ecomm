<?php

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Staff\Models\Staff;
use App\Filament\Exports\ProductExporter;
use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\Products\Pages\ListProducts;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

/** Runs the importer on one CSV row (columns mapped by name) and returns the import. */
function importRow(array $row, ?Import $import = null): Import
{
    $import ??= Import::query()->create([
        'file_name' => 'productos.csv', 'file_path' => 'productos.csv', 'importer' => ProductImporter::class,
        'total_rows' => 1, 'user_id' => auth()->id(),
    ]);
    $columns = array_keys($row);
    $importer = new ProductImporter($import, array_combine($columns, $columns), []);
    $importer($row);

    return $import;
}

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    Category::factory()->create(['name' => 'Cocina']);
});

it('creates products and variants from rows, grouping variants by product name', function () {
    importRow(['sku' => 'SART-20', 'product' => 'Sartén antiadherente', 'name' => '20 cm', 'price_cents' => '12,50', 'stock' => '5', 'categories' => 'Cocina', 'publish' => 'sí']);
    importRow(['sku' => 'SART-24', 'product' => 'Sartén antiadherente', 'name' => '24 cm', 'price_cents' => '15.90', 'stock' => '3', 'publish' => 'sí']);

    $product = Product::query()->sole();
    expect($product->status)->toBe(ProductStatus::Published)
        ->and($product->variants->pluck('sku')->all())->toBe(['SART-20', 'SART-24'])
        ->and($product->variants->firstWhere('sku', 'SART-24'))->price_cents->toBe(1590)->stock->toBe(3);
});

it('updates an existing variant and logs stock changes', function () {
    importRow(['sku' => 'TAZA-1', 'product' => 'Taza', 'price_cents' => '4', 'stock' => '10']);
    importRow(['sku' => 'TAZA-1', 'price_cents' => '4,50', 'stock' => '7']);

    $variant = ProductVariant::query()->where('sku', 'TAZA-1')->sole();
    expect($variant)->price_cents->toBe(450)->stock->toBe(7)
        ->and(Activity::query()->where('event', 'stock_adjusted')->latest('id')->first()->properties['reason'])->toBe('Importación CSV');
});

it('rejects rows with unknown categories or missing product names, without writing anything', function () {
    expect(fn () => importRow(['sku' => 'X-1', 'product' => 'Vaso', 'price_cents' => '2', 'categories' => 'Jardín']))
        ->toThrow(RowImportFailedException::class, 'No existe la categoría «Jardín»');
    expect(fn () => importRow(['sku' => 'X-2', 'product' => '', 'price_cents' => '2']))
        ->toThrow(RowImportFailedException::class, 'Falta el nombre del producto');

    expect(Product::query()->count())->toBe(0)->and(ProductVariant::query()->count())->toBe(0);
});

it('keeps a product as a draft when it cannot be published yet', function () {
    expect(fn () => importRow(['sku' => 'P-1', 'product' => 'Plato', 'price_cents' => '3', 'publish' => 'sí']))
        ->toThrow(RowImportFailedException::class, 'Guardado como borrador');

    expect(Product::query()->sole()->status)->toBe(ProductStatus::Draft);
});

it('exports the headers the importer recognises', function () {
    $headers = collect(ProductExporter::getColumns())->map->getLabel()->all();
    $guesses = collect(ProductImporter::getColumns())->flatMap(fn ($c) => [$c->getName(), ...$c->getGuesses()])->map(fn ($g) => strtolower($g))->all();

    foreach ($headers as $header) {
        expect($guesses)->toContain(strtolower($header));
    }
});

it('shows import and export only to staff allowed to use them', function () {
    Livewire::test(ListProducts::class)
        ->assertActionVisible('import')->assertActionVisible('export');

    actingAs(staffWithRole('Almacén'), Staff::GUARD);
    Livewire::test(ListProducts::class)
        ->assertActionHidden('import')->assertActionVisible('export');
});
