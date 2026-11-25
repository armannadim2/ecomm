<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Catalog\VariantCombinations;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Products\Pages\EditProduct;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('builds every size × colour combination with unique SKUs', function () {
    ProductVariant::factory()->for(Product::factory())->create(['sku' => 'CAMISETA-S-AZUL']);

    $variants = VariantCombinations::generate(['S', 'M', ' M '], ['Azul', 'Rojo'], 'Camiseta', existingNames: ['m / rojo']);

    expect(array_column($variants, 'name'))->toBe(['S / Azul', 'S / Rojo', 'M / Azul'])
        ->and(array_column($variants, 'sku'))->toBe(['CAMISETA-S-AZUL-2', 'CAMISETA-S-ROJO', 'CAMISETA-M-AZUL']);
});

it('works with a single list and refuses empty or huge ones', function () {
    expect(array_column(VariantCombinations::generate([], ['Verde'], 'Taza'), 'name'))->toBe(['Verde']);
    expect(fn () => VariantCombinations::generate([], [], 'X'))->toThrow(DomainRuleException::class);
    expect(fn () => VariantCombinations::generate(range(1, 11), range(1, 10), 'X'))->toThrow(DomainRuleException::class, 'máximo');
});

it('adds the generated variants on the product page and saves them', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $product = Product::factory()->sellable(priceCents: 1500, stock: 2)->create(['name' => 'Camiseta']);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callFormComponentAction('variants', 'generateVariants', [
            'first' => ['S', 'M'], 'second' => ['Azul'], 'sku_prefix' => 'CAM', 'price_cents' => '12.50', 'stock' => 4,
        ])
        ->assertNotified('2 variantes añadidas')
        ->call('save')
        ->assertHasNoFormErrors();

    $variants = $product->fresh()->variants;
    expect($variants)->toHaveCount(3)
        ->and($variants->pluck('sku')->all())->toContain('CAM-S-AZUL', 'CAM-M-AZUL')
        ->and($variants->firstWhere('sku', 'CAM-M-AZUL'))->price_cents->toBe(1250)->stock->toBe(4);
});
