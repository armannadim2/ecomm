<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

function cartProduct(int $priceCents = 2000, int $stock = 5, ?int $compareAt = null): Product
{
    $product = Product::factory()->sellable(priceCents: $priceCents, stock: $stock)->create();
    app(PublishProduct::class)->handle($product);
    if ($compareAt) {
        $product->variants()->update(['compare_at_price_cents' => $compareAt]);
    }

    return $product->fresh('variants');
}

function pickupCheckout(array $overrides = []): array
{
    return $overrides + [
        'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store',
        'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => 'marta@example.test', 'phone' => '612345678', 'accept_terms' => '1',
    ];
}

beforeEach(fn () => Mail::fake());

it('adds products to the cart and shows the totals', function () {
    $product = cartProduct(2000, compareAt: 2500);

    // Stays on the page the customer was on (no jump to the checkout).
    $this->from('/producto/'.$product->slug)
        ->post('/cesta', ['variant' => $product->variants->first()->ulid, 'quantity' => 2])
        ->assertRedirect('/producto/'.$product->slug);

    get('/cesta')->assertOk()
        ->assertSee($product->name)
        ->assertSee('40,00 €')   // 2 × 20,00
        ->assertSee('10,00 €');  // savings 2 × 5,00
});

it('adds to the cart in the background and returns the mini cart', function () {
    $product = cartProduct(2000);
    $variant = $product->variants->first();

    $this->postJson('/cesta', ['variant' => $variant->ulid, 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('count', 2)
        ->assertJsonPath('subtotal', '40,00 €')
        ->assertJsonPath('lines.0.name', $product->name)
        ->assertJsonPath('lines.0.quantity', 2)
        ->assertJsonPath('message', "«{$product->name}» se ha añadido a tu cesta.");

    $this->getJson('/cesta/resumen')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('count', 2)
        ->assertJsonPath('lines.0.total', '40,00 €');

    $soldOut = cartProduct(stock: 0)->variants->first();
    $this->postJson('/cesta', ['variant' => $soldOut->ulid])->assertStatus(422)->assertJsonStructure(['message']);
    $this->postJson('/cesta', ['variant' => 'missing'])->assertStatus(422);
});

it('shows the header mini cart instead of linking straight to checkout', function () {
    $product = cartProduct(2000);
    post('/cesta', ['variant' => $product->variants->first()->ulid]);

    get('/')->assertOk()
        ->assertSee('id="mini-cesta"', false)
        ->assertSee('Finalizar compra');
});

it('never puts more units in the cart than there are in stock', function () {
    $variant = cartProduct(stock: 2)->variants->first();

    post('/cesta', ['variant' => $variant->ulid, 'quantity' => 5]);

    expect(session('cart'))->toBe([$variant->ulid => 2]);
});

it('refuses sold-out and unpublished products', function () {
    $soldOut = cartProduct(stock: 0)->variants->first();
    $draft = Product::factory()->sellable()->create()->variants->first();

    post('/cesta', ['variant' => $soldOut->ulid])->assertSessionHasErrors('cart');
    post('/cesta', ['variant' => $draft->ulid])->assertSessionHasErrors('cart');
    expect(session('cart', []))->toBe([]);
});

it('updates and removes cart lines', function () {
    $variant = cartProduct()->variants->first();
    post('/cesta', ['variant' => $variant->ulid]);

    patch('/cesta/'.$variant->ulid, ['quantity' => 3]);
    expect(session('cart'))->toBe([$variant->ulid => 3]);

    delete('/cesta/'.$variant->ulid);
    expect(session('cart'))->toBe([]);
});

it('places a Click & Collect order paid at the shop, reserves stock and empties the cart', function () {
    $product = cartProduct(2000, stock: 5);
    $variant = $product->variants->first();
    post('/cesta', ['variant' => $variant->ulid, 'quantity' => 2]);

    $response = post('/cesta/confirmar', pickupCheckout());

    $order = Order::query()->with('items')->sole();
    $response->assertRedirect(route('order.show', $order));

    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->fulfillment)->toBe(Fulfillment::Pickup)
        ->and($order->number)->toMatch('/^TM-\d{4}-\d{5}$/')
        ->and($order->pickup_code)->toMatch('/^TM-[A-Z2-9]{6}$/')
        ->and($order->total_cents)->toBe(4000)
        ->and($order->tax_cents)->toBe(694) // 4000 - 4000 / 1.21
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($variant->fresh()->stock)->toBe(3)
        ->and(session('cart'))->toBe([]);

    Mail::assertQueued(OrderPlaced::class, fn ($mail) => $mail->hasTo('marta@example.test'));

    get(route('order.show', $order))->assertOk()
        ->assertSee($order->number)
        ->assertSee($order->pickup_code)
        ->assertSee('<svg', false); // QR code
});

it('does not allow home delivery or online payment until they are connected', function () {
    $variant = cartProduct()->variants->first();
    post('/cesta', ['variant' => $variant->ulid]);

    post('/cesta/confirmar', pickupCheckout(['payment_method' => 'bizum']))->assertSessionHasErrors('checkout');
    post('/cesta/confirmar', pickupCheckout([
        'fulfillment' => 'delivery', 'payment_method' => 'card',
        'ship_line1' => 'Carrer Major 1', 'ship_postal_code' => '08012', 'ship_city' => 'Barcelona',
    ]))->assertSessionHasErrors('checkout');

    expect(Order::query()->count())->toBe(0);
});

it('validates the customer data', function () {
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);

    post('/cesta/confirmar', pickupCheckout(['email' => 'no-es-un-correo', 'phone' => '12']))
        ->assertSessionHasErrors(['email', 'phone']);
});

it('refuses the order when stock ran out after adding to the cart', function () {
    $variant = cartProduct(stock: 2)->variants->first();
    post('/cesta', ['variant' => $variant->ulid, 'quantity' => 2]);
    $variant->update(['stock' => 1]);

    // The cart caps the quantity to the stock left, so the order is for 1 unit.
    post('/cesta/confirmar', pickupCheckout());

    expect(Order::query()->sole()->items->first()->quantity)->toBe(1)
        ->and($variant->fresh()->stock)->toBe(0);
});

it('only shows an order to its buyer, its customer account or a signed link', function () {
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
    post('/cesta/confirmar', pickupCheckout());
    $order = Order::query()->sole();

    // A different browser cannot see it...
    app('session.store')->flush();
    get(route('order.show', $order))->assertNotFound();

    // ...but the signed link from the e-mail works.
    get(URL::signedRoute('order.show', $order))->assertOk();
});

it('links the order to the logged-in customer and lists it in the account', function () {
    $customer = Customer::factory()->create();
    actingAs($customer, 'web');
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
    post('/cesta/confirmar', pickupCheckout(['email' => $customer->email]));

    $order = Order::query()->sole();
    expect($order->customer_id)->toBe($customer->id);
    get('/cuenta')->assertOk()->assertSee($order->number);
});

it('moves an order through its statuses and puts stock back when cancelled', function () {
    $variant = cartProduct(stock: 5)->variants->first();
    post('/cesta', ['variant' => $variant->ulid, 'quantity' => 2]);
    post('/cesta/confirmar', pickupCheckout());
    $order = Order::query()->sole();
    $change = app(ChangeOrderStatus::class);

    $change->handle($order, OrderStatus::Preparing);
    $change->handle($order, OrderStatus::ReadyForPickup);
    $change->handle($order, OrderStatus::Completed);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);

    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', pickupCheckout());
    $second = Order::query()->latest('id')->first();
    expect($variant->fresh()->stock)->toBe(2);

    $change->handle($second, OrderStatus::Cancelled);
    expect($variant->fresh()->stock)->toBe(3);
});

it('lets order staff open an order in the admin', function () {
    Filament::setCurrentPanel('admin');
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
    post('/cesta/confirmar', pickupCheckout());
    $order = Order::query()->sole();

    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertOk()
        ->assertSee($order->number)
        ->assertSee('Empezar a preparar');
});
