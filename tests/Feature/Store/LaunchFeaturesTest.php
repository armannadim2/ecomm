<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\Page;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function launchProduct(int $stock = 5): Product
{
    $product = Product::factory()->sellable(priceCents: 1500, stock: $stock)->create();
    app(PublishProduct::class)->handle($product);

    return $product->fresh('variants');
}

it('serves catalogue pages from the page cache to anonymous visitors and refreshes it when products change', function () {
    $product = launchProduct();

    get('/producto/'.$product->slug)->assertOk()->assertHeader('X-Page-Cache', 'miss');
    get('/producto/'.$product->slug)->assertOk()->assertHeader('X-Page-Cache', 'hit')->assertSee($product->name);

    $product->update(['name' => 'Nombre Nuevo']);
    get('/producto/'.$product->slug)->assertHeader('X-Page-Cache', 'miss')->assertSee('Nombre Nuevo');
});

it('swaps in the visitor\'s own CSRF token on cached pages', function () {
    $product = launchProduct();
    get('/producto/'.$product->slug);

    $html = get('/producto/'.$product->slug)->assertHeader('X-Page-Cache', 'hit')->getContent();

    expect($html)->not->toContain('__CSRF_TOKEN_PLACEHOLDER__')->toContain(csrf_token());
});

it('never caches pages for visitors with something in the cart or logged in', function () {
    $product = launchProduct();
    post('/cesta', ['variant' => $product->variants->first()->ulid]);

    get('/producto/'.$product->slug)->assertOk()->assertHeaderMissing('X-Page-Cache');

    actingAs(Customer::factory()->create(), 'web');
    get('/')->assertOk()->assertHeaderMissing('X-Page-Cache');
});

it('shows the legal pages in Catalan', function () {
    Page::query()->where('slug', 'condiciones-generales')->update(['is_published' => true]);

    get('/idioma/ca');
    get('/pagina/condiciones-generales')->assertOk()
        ->assertSee('Condicions generals de contractació')
        ->assertSee('Dret de desistiment');

    get('/idioma/es');
    get('/pagina/condiciones-generales')->assertOk()->assertSee('Derecho de desistimiento');
});

it('keeps the cart of a customer across devices', function () {
    $product = launchProduct();
    $customer = Customer::factory()->create(['password' => 'secret-Pass-123']);

    actingAs($customer, 'web');
    post('/cesta', ['variant' => $product->variants->first()->ulid, 'quantity' => 2]);
    expect($customer->fresh()->saved_cart)->toBe([$product->variants->first()->ulid => 2]);

    // Another device: log in with an empty cart.
    auth('web')->logout();
    app('session.store')->flush();
    post('/cuenta/acceder', ['email' => $customer->email, 'password' => 'secret-Pass-123']);

    expect(session('cart'))->toBe([$product->variants->first()->ulid => 2]);
});

it('puts the products of an old order back in the cart', function () {
    Mail::fake();
    $customer = Customer::factory()->create();
    $product = launchProduct();
    actingAs($customer, 'web');
    post('/cesta', ['variant' => $product->variants->first()->ulid, 'quantity' => 2]);
    post('/cesta/confirmar', [
        'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store', 'first_name' => 'Marta', 'last_name' => 'Puig',
        'email' => $customer->email, 'phone' => '612345678', 'accept_terms' => '1',
    ]);
    $order = Order::query()->sole();
    expect(session('cart'))->toBe([]);

    post(route('account.reorder', $order))->assertRedirect(route('cart.show'));
    expect(session('cart'))->toBe([$product->variants->first()->ulid => 2]);

    actingAs(Customer::factory()->create(), 'web');
    post(route('account.reorder', $order))->assertNotFound();
});
