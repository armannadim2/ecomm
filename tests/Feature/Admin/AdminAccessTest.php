<?php

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Product;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Brands\Pages\ManageBrands;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use App\Filament\Resources\Staff\StaffResource;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('sends guests to the login page', function () {
    get('/admin')->assertRedirect('/admin/login');
});

it('shows the login page in Spanish', function () {
    get('/admin/login')->assertOk()->assertSee('lang="es"', false)->assertSee('Acceso');
});

it('sends staff without two-step verification to set it up', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    get('/admin')->assertRedirect('/admin/multi-factor-authentication/set-up');
});

it('blocks deactivated staff', function () {
    $staff = Staff::factory()->inactive()->owner()->create()->fresh();

    actingAs($staff, Staff::GUARD);

    get('/admin')->assertForbidden();
});

it('does not let customers (web guard) into the admin', function () {
    actingAs(User::factory()->create(), 'web');

    get('/admin')->assertRedirect('/admin/login');
});

it('renders every admin screen for the owner', function (string $page) {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test($page)->assertOk();
})->with([
    ListProducts::class,
    CreateProduct::class,
    ManageCategories::class,
    ManageBrands::class,
    ManageStaff::class,
    ManageRoles::class,
    ListActivities::class,
]);

it('renders the product edit page', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $product = Product::factory()->sellable()->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])->assertOk();
});

it('hides team screens from warehouse staff', function () {
    actingAs(staffWithRole('Almacén'), Staff::GUARD);

    expect(StaffResource::canViewAny())->toBeFalse()
        ->and(ActivityResource::canViewAny())->toBeFalse();
});

it('creates a brand from the admin', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(ManageBrands::class)
        ->callAction('create', data: ['name' => 'Marca Nueva', 'is_visible' => true])
        ->assertHasNoActionErrors();

    expect(Brand::query()->where('slug', 'marca-nueva')->exists())->toBeTrue();
});

it('publishes a product from the product list', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $product = Product::factory()->sellable()->create();

    Livewire::test(ListProducts::class)
        ->callTableAction('publish', $product)
        ->assertNotified('Producto publicado');

    expect($product->fresh()->isPublished())->toBeTrue();
});

it('explains why a product cannot be published', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $product = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->callTableAction('publish', $product)
        ->assertNotified('Todavía no se puede publicar');

    expect($product->fresh()->isPublished())->toBeFalse();
});
