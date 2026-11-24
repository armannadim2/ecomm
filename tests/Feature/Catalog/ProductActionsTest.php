<?php

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Actions\DuplicateProduct;
use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Actions\UnpublishProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Spatie\Activitylog\Models\Activity;

describe('publishing', function () {
    it('publishes a product that is ready to sell', function () {
        $product = Product::factory()->sellable()->create();

        app(PublishProduct::class)->handle($product);

        expect($product->fresh())
            ->status->toBe(ProductStatus::Published)
            ->published_at->not->toBeNull();
    });

    it('refuses a product without variants', function () {
        $product = Product::factory()->create();

        app(PublishProduct::class)->handle($product);
    })->throws(DomainRuleException::class, 'variante');

    it('refuses a variant priced at 0 €', function () {
        $product = Product::factory()->sellable(priceCents: 0)->create();

        app(PublishProduct::class)->handle($product);
    })->throws(DomainRuleException::class, 'precio');

    it('refuses a product without a category', function () {
        $product = Product::factory()->has(ProductVariant::factory(), 'variants')->create();

        app(PublishProduct::class)->handle($product);
    })->throws(DomainRuleException::class, 'categoría');

    it('hides or archives a published product', function () {
        $product = Product::factory()->sellable()->create();
        app(PublishProduct::class)->handle($product);

        app(UnpublishProduct::class)->handle($product);
        expect($product->fresh()->status)->toBe(ProductStatus::Draft);

        app(UnpublishProduct::class)->handle($product, archive: true);
        expect($product->fresh()->status)->toBe(ProductStatus::Archived);
    });

    it('does not allow status to be mass-assigned', function () {
        $product = Product::factory()->create();

        $product->fill(['status' => 'published']);
    })->throws(MassAssignmentException::class);
});

describe('stock', function () {
    it('adjusts stock and records the reason', function () {
        $variant = ProductVariant::factory()->create(['stock' => 5]);

        app(AdjustStock::class)->handle($variant, 10, 'Recepción de mercancía');

        expect($variant->fresh()->stock)->toBe(15);

        $log = Activity::query()->where('event', 'stock_adjusted')->latest('id')->first();
        expect($log->properties->all())->toMatchArray([
            'before' => 5, 'change' => 10, 'after' => 15, 'reason' => 'Recepción de mercancía',
        ]);
    });

    it('never lets stock go below zero', function () {
        $variant = ProductVariant::factory()->create(['stock' => 2]);

        app(AdjustStock::class)->handle($variant, -3, 'Producto dañado o perdido');
    })->throws(DomainRuleException::class, 'quedan 2');

    it('requires a reason', function () {
        $variant = ProductVariant::factory()->create();

        app(AdjustStock::class)->handle($variant, 1, '  ');
    })->throws(DomainRuleException::class);
});

describe('duplicating', function () {
    it('copies a product as a draft with unique slug and SKUs and no stock', function () {
        $product = Product::factory()->sellable(stock: 7)->create(['name' => 'Camiseta azul']);
        app(PublishProduct::class)->handle($product);
        $originalSku = $product->variants->first()->sku;

        $copy = app(DuplicateProduct::class)->handle($product->fresh('variants'));

        expect($copy)
            ->name->toBe('Camiseta azul (copia)')
            ->slug->toBe('camiseta-azul-copia')
            ->status->toBe(ProductStatus::Draft)
            ->and($copy->variants()->first())
            ->sku->toBe("{$originalSku}-COPIA")
            ->stock->toBe(0)
            ->and($copy->categories()->count())->toBe(1);
    });
});

describe('slugs', function () {
    it('generates unique slugs from the name', function () {
        $a = Product::factory()->create(['name' => 'Sudadera Básica']);
        $b = Product::factory()->create(['name' => 'Sudadera Básica']);

        expect($a->slug)->toBe('sudadera-basica')
            ->and($b->slug)->toBe('sudadera-basica-2');
    });

    it('uses a public ulid instead of the numeric id in URLs', function () {
        $product = Product::factory()->create();

        expect($product->getRouteKey())->toBe($product->ulid)
            ->and(strlen($product->ulid))->toBe(26);
    });
});
