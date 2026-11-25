<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Mail\OrderReadyForPickup;
use App\Domain\Orders\Models\Order;
use App\Domain\Staff\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/** Places a Click & Collect order through the shop, as a customer would. */
function mailTestOrder(): Order
{
    $product = Product::factory()->sellable(priceCents: 2500, stock: 5)->create(['name' => 'Joc de paelles']);
    app(PublishProduct::class)->handle($product);
    post('/cesta', ['variant' => $product->fresh('variants')->variants->first()->ulid, 'quantity' => 2]);
    post('/cesta/confirmar', [
        'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store',
        'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => 'marta@example.test', 'phone' => '612345678',
    ]);

    return Order::query()->sole();
}

beforeEach(fn () => Mail::fake());

it('renders the receipt with the items, totals, pickup code and QR', function () {
    $order = mailTestOrder();

    $html = (new OrderPlaced($order))->render();

    expect($html)
        ->toContain($order->number)
        ->toContain($order->pickup_code)
        ->toContain('Joc de paelles')
        ->toContain('50,00')
        ->toContain('data:image/png;base64,') // QR (attached with cid: when really sent)
        ->toContain('Avinguda Meridiana 507')
        ->toContain('hello@totymes.com');
});

it('sends the receipt in the language of the order', function () {
    $order = mailTestOrder();
    $order->update(['locale' => 'ca']);

    $html = (new OrderPlaced($order))->locale('ca')->render();

    expect($html)->toContain('Resum de la compra')->not->toContain('Resumen de la compra');
});

it('e-mails the customer when a pickup order is ready', function () {
    $order = mailTestOrder();
    $change = app(ChangeOrderStatus::class);

    $change->handle($order, OrderStatus::Preparing);
    Mail::assertNotQueued(OrderReadyForPickup::class);

    $change->handle($order, OrderStatus::ReadyForPickup);
    Mail::assertQueued(OrderReadyForPickup::class, fn ($mail) => $mail->hasTo('marta@example.test'));

    expect((new OrderReadyForPickup($order->fresh()))->render())
        ->toContain('listo para recoger')
        ->toContain($order->pickup_code);
});

it('lets order staff preview the e-mails, and nobody else', function () {
    $order = mailTestOrder();
    $url = route('admin.order-mail.preview', [$order, 'listo-para-recoger']);

    get($url)->assertRedirect();

    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole('Almacén'), Staff::GUARD);
    get($url)->assertForbidden();

    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    get($url.'?locale=en')->assertOk()->assertSee('ready to collect');
    get(route('admin.order-mail.preview', [$order, 'otro']))->assertNotFound();
});
