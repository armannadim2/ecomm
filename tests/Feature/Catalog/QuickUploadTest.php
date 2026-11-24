<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Actions\QuickCreateProduct;
use App\Domain\Catalog\Actions\RejectProduct;
use App\Domain\Catalog\Actions\SubmitProductForReview;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Pages\QuickUpload;
use App\Filament\Resources\Products\ProductResource;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    Filament::setCurrentPanel('admin');
});

function storedPhoto(): string
{
    return UploadedFile::fake()->image('foto.jpg', 800, 800)->store('quick-uploads', 'local');
}

it('publishes at once when the uploader can publish', function () {
    $manager = staffWithRole('Gerente');
    $category = Category::factory()->create();

    $product = app(QuickCreateProduct::class)->handle([
        'name' => 'Chaqueta vaquera', 'price_cents' => 3500, 'category_id' => $category->id,
        'stock' => 2, 'photos' => [storedPhoto(), storedPhoto()],
    ], $manager);

    expect($product->status)->toBe(ProductStatus::Published)
        ->and($product->created_by_staff_id)->toBe($manager->id)
        ->and($product->variants()->first()->price_cents)->toBe(3500)
        ->and($product->variants()->first()->sku)->toStartWith('P-')
        ->and($product->getMedia(Product::IMAGES))->toHaveCount(2)
        ->and(Storage::disk('local')->allFiles('quick-uploads'))->toBeEmpty();
});

it('sends a colaborador upload for review and notifies reviewers', function () {
    $colaborador = staffWithRole('Colaborador');
    $owner = staffWithRole(Staff::OWNER_ROLE);
    $manager = staffWithRole('Gerente');
    $warehouse = staffWithRole('Almacén');

    $product = app(QuickCreateProduct::class)->handle([
        'name' => 'Bolso de piel', 'price_cents' => 4900,
        'category_id' => Category::factory()->create()->id, 'photos' => [storedPhoto()],
    ], $colaborador);

    expect($product->status)->toBe(ProductStatus::PendingReview)
        ->and($product->submitted_at)->not->toBeNull()
        ->and($owner->notifications()->count())->toBe(1)
        ->and($manager->notifications()->count())->toBe(1)
        ->and($warehouse->notifications()->count())->toBe(0)
        ->and($colaborador->notifications()->count())->toBe(0);
});

it('lets a reviewer approve and tells the uploader', function () {
    $colaborador = staffWithRole('Colaborador');
    $owner = staffWithRole(Staff::OWNER_ROLE);
    $product = Product::factory()->sellable()->create();
    $product->forceFill(['created_by_staff_id' => $colaborador->id])->save();
    app(SubmitProductForReview::class)->handle($product);

    app(PublishProduct::class)->handle($product->fresh(), $owner);

    expect($product->fresh())
        ->status->toBe(ProductStatus::Published)
        ->reviewed_by_staff_id->toBe($owner->id)
        ->and($colaborador->notifications()->count())->toBe(1);
});

it('lets a reviewer send it back with a note', function () {
    $colaborador = staffWithRole('Colaborador');
    $product = Product::factory()->sellable()->create();
    $product->forceFill(['created_by_staff_id' => $colaborador->id])->save();
    app(SubmitProductForReview::class)->handle($product);

    app(RejectProduct::class)->handle($product->fresh(), staffWithRole('Gerente'), 'La foto está borrosa');

    expect($product->fresh())
        ->status->toBe(ProductStatus::Draft)
        ->review_note->toBe('La foto está borrosa')
        ->and($colaborador->notifications()->count())->toBe(1);
});

it('requires a note to send a product back', function () {
    $product = Product::factory()->sellable()->create();
    app(SubmitProductForReview::class)->handle($product);

    app(RejectProduct::class)->handle($product->fresh(), staffWithRole('Gerente'), ' ');
})->throws(DomainRuleException::class);

describe('colaborador permissions', function () {
    it('can edit own drafts but not live products or other people\'s', function () {
        $colaborador = staffWithRole('Colaborador');
        $own = Product::factory()->create();
        $own->forceFill(['created_by_staff_id' => $colaborador->id])->save();
        $other = Product::factory()->create();

        expect($colaborador->can('update', $own))->toBeTrue()
            ->and($colaborador->can('update', $other))->toBeFalse()
            ->and($colaborador->can('publish', $own))->toBeFalse()
            ->and($colaborador->can('submitForReview', $own))->toBeTrue();

        $own->forceFill(['status' => ProductStatus::Published])->save();
        expect($colaborador->can('update', $own->fresh()))->toBeFalse();
    });

    it('only lists own products in the admin', function () {
        $colaborador = staffWithRole('Colaborador');
        $own = Product::factory()->create(['name' => 'Mío']);
        $own->forceFill(['created_by_staff_id' => $colaborador->id])->save();
        Product::factory()->create(['name' => 'De otro']);

        actingAs($colaborador, Staff::GUARD);

        expect(ProductResource::getEloquentQuery()->pluck('name')->all())->toBe(['Mío']);
    });
});

it('renders the quick upload page with the right button', function () {
    actingAs(staffWithRole('Colaborador'), Staff::GUARD);
    Livewire::test(QuickUpload::class)->assertOk()->assertSee('Enviar a revisión');

    actingAs(staffWithRole('Gerente'), Staff::GUARD);
    Livewire::test(QuickUpload::class)->assertOk()->assertSee('Publicar');
});

it('hides quick upload from staff who cannot add products', function () {
    actingAs(staffWithRole('Pedidos'), Staff::GUARD);

    expect(QuickUpload::canAccess())->toBeFalse();
});
