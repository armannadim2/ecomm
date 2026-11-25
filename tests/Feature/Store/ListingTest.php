<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function listedProduct(Category $category, string $name, int $priceCents, int $stock = 5, ?Brand $brand = null): Product
{
    $product = Product::factory()->sellable(priceCents: $priceCents, stock: $stock)->create(['name' => $name, 'brand_id' => $brand?->id]);
    $product->categories()->sync([$category->id]);
    app(PublishProduct::class)->handle($product);

    return $product->fresh();
}

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'Cocina', 'slug' => 'cocina']);
    $this->bra = Brand::factory()->create(['name' => 'BRA', 'slug' => 'bra']);
    $this->monix = Brand::factory()->create(['name' => 'Monix', 'slug' => 'monix']);

    listedProduct($this->category, 'Sartén Grande', 3500, brand: $this->bra);
    listedProduct($this->category, 'Olla Pequeña', 1200, brand: $this->monix);
    listedProduct($this->category, 'Wok Agotado', 2500, stock: 0, brand: $this->bra);
});

it('filters a category by brand, price and stock, with facet counts and removable chips', function () {
    get('/categoria/cocina')->assertOk()
        ->assertSee('Sartén Grande')->assertSee('Olla Pequeña')->assertSee('Wok Agotado')
        ->assertSee('3 productos');

    get('/categoria/cocina?marca[]=bra')->assertOk()
        ->assertSee('Sartén Grande')->assertSee('Wok Agotado')->assertDontSee('Olla Pequeña')
        ->assertSee('Filtros activos')->assertSee('Quitar todos los filtros');

    get('/categoria/cocina?min=20&max=30')->assertOk()
        ->assertSee('Wok Agotado')->assertDontSee('Sartén Grande')->assertDontSee('Olla Pequeña');

    get('/categoria/cocina?stock=1')->assertOk()
        ->assertDontSee('Wok Agotado')->assertSee('Sartén Grande');
});

it('ignores malformed filter values', function () {
    get('/categoria/cocina?marca[]=<script>&min=abc&categoria[]=x')->assertOk()
        ->assertSee('3 productos')->assertDontSee('<script>', false);
});

it('filters search results by section', function () {
    $other = Category::factory()->create(['name' => 'Baño', 'slug' => 'bano']);
    listedProduct($other, 'Sartén de Viaje', 900);

    get('/buscar?q=Sartén')->assertOk()
        ->assertSee('Sartén Grande')->assertSee('Sartén de Viaje')->assertSee('Más relevantes');

    get('/buscar?q=Sartén&categoria=bano')->assertOk()
        ->assertSee('Sartén de Viaje')->assertDontSee('Sartén Grande');
});

it('lists every department on the categories page', function () {
    Category::factory()->create(['name' => 'Oculta', 'is_visible' => false]);

    get('/categorias')->assertOk()
        ->assertSee('Cocina')->assertSee('3 productos')
        ->assertSee('href="'.route('store.category', 'cocina').'"', false)
        ->assertDontSee('Oculta');
});

it('shows the open pickup order with its QR code on the account page', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['first_name' => 'Marta']);
    $variant = Product::query()->where('name', 'Olla Pequeña')->sole()->variants->first();

    actingAs($customer, 'web');
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', [
        'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store',
        'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => $customer->email, 'phone' => '612345678',
    ])->assertRedirect();

    $order = $customer->orders()->sole();

    get('/cuenta')->assertOk()
        ->assertSee('Hola, Marta')
        ->assertSee('Pedido en curso')
        ->assertSee($order->number)
        ->assertSee('Olla Pequeña')
        ->assertSee($order->pickup_code)
        ->assertSee('<svg', false);
});
