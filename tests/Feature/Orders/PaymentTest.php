<?php

use App\Domain\Invoices\Enums\InvoiceType;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Marketing\Settings\AnalyticsSettings;
use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Mail\OrderRefunded;
use App\Domain\Orders\Mail\OrderShipped;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Payments\Actions\ReconcilePayments;
use App\Domain\Payments\Actions\RefundOrder;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Gateways\Redsys\RedsysSignature;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Pages\ManageAnalyticsSettings;
use App\Filament\Pages\ManageCheckoutSettings;
use App\Filament\Pages\ManagePaymentSettings;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Support\ConfirmPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

/** Redsys public test key (integration guide). */
const REDSYS_TEST_KEY = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

function enablePayments(): void
{
    $p = app(PaymentSettings::class);
    $p->redsys_card_enabled = true;
    $p->redsys_bizum_enabled = true;
    $p->redsys_merchant_code = '999008881';
    $p->redsys_terminal = '1';
    $p->redsys_secret_key = REDSYS_TEST_KEY;
    $p->paypal_enabled = true;
    $p->paypal_client_id = 'client-id';
    $p->paypal_client_secret = 'client-secret';
    $p->paypal_webhook_id = 'WH-1';
    $p->save();

    $c = app(CheckoutSettings::class);
    $c->delivery_enabled = true;
    $c->delivery_cost_cents = 395;
    $c->balearics_cost_cents = 795;
    $c->free_delivery_threshold_cents = 3500;
    $c->local_delivery_enabled = true;
    $c->local_delivery_postcodes = '08001-08042';
    $c->local_delivery_cost_cents = 295;
    $c->save();
}

function deliveryCheckout(array $overrides = []): array
{
    return $overrides + [
        'fulfillment' => 'delivery', 'payment_method' => 'card',
        'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => 'marta@example.test', 'phone' => '612345678',
        'ship_line1' => 'Carrer Gran 1', 'ship_postal_code' => '08901', 'ship_city' => "L'Hospitalet", 'accept_terms' => '1',
    ];
}

/** Places an online order for one product of 20 € and returns it. */
function onlineOrder(array $overrides = [], int $stock = 5): Order
{
    post('/cesta', ['variant' => cartProduct(2000, stock: $stock)->variants->first()->ulid]);
    post('/cesta/confirmar', deliveryCheckout($overrides));

    return Order::query()->latest('id')->firstOrFail();
}

/** Signed Redsys notification as the bank would send it. */
function redsysNotification(Payment $payment, string $response = '0000', ?int $amount = null): array
{
    $encoded = RedsysSignature::encodeParameters([
        'Ds_Date' => '26/11/2026', 'Ds_Hour' => '10:00',
        'Ds_Amount' => (string) ($amount ?? $payment->amount_cents),
        'Ds_Currency' => '978', 'Ds_Order' => $payment->reference,
        'Ds_MerchantCode' => '999008881', 'Ds_Terminal' => '001',
        'Ds_Response' => $response, 'Ds_AuthorisationCode' => '123456', 'Ds_TransactionType' => '0',
    ]);

    return [
        'Ds_SignatureVersion' => RedsysSignature::VERSION,
        'Ds_MerchantParameters' => $encoded,
        'Ds_Signature' => strtr(RedsysSignature::sign($encoded, (string) $payment->reference, REDSYS_TEST_KEY), '+/', '-_'),
    ];
}

beforeEach(function () {
    Mail::fake();
    enablePayments();
});

it('keeps online methods and home delivery hidden until their credentials are saved', function () {
    $p = app(PaymentSettings::class);
    $p->redsys_secret_key = null;
    $p->paypal_client_secret = null;
    $p->save();

    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
    post('/cesta/confirmar', deliveryCheckout())->assertSessionHasErrors('checkout');

    expect(Order::query()->count())->toBe(0);
});

it('requires accepting the sale conditions', function () {
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);

    post('/cesta/confirmar', deliveryCheckout(['accept_terms' => null]))->assertSessionHasErrors('accept_terms');
});

it('prices home delivery by zone: local round, Peninsula and Balearics', function () {
    $variant = cartProduct(2000)->variants->first();
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', deliveryCheckout(['ship_postal_code' => '08012', 'checkout_token' => 'a1']));
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', deliveryCheckout(['ship_postal_code' => '28001', 'checkout_token' => 'a2']));
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', deliveryCheckout(['ship_postal_code' => '07001', 'checkout_token' => 'a3']));

    expect(Order::query()->orderBy('id')->get()->map(fn ($o) => [$o->shipping_zone, $o->shipping_cents])->all())
        ->toBe([['local', 295], ['peninsula', 395], ['balearics', 795]]);

    // Canarias is refused.
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', deliveryCheckout(['ship_postal_code' => '35001', 'checkout_token' => 'a4']))->assertSessionHasErrors();
});

it('uses the weight rates when they are set', function () {
    $c = app(CheckoutSettings::class);
    $c->weight_rates = [['max_kg' => 2, 'peninsula_cents' => 450, 'balearics_cents' => 900], ['max_kg' => 10, 'peninsula_cents' => 790, 'balearics_cents' => 1500]];
    $c->save();

    $product = cartProduct(1000);
    $product->variants()->update(['weight_grams' => 1500]);
    post('/cesta', ['variant' => $product->variants->first()->ulid, 'quantity' => 2]); // 3 kg

    post('/cesta/confirmar', deliveryCheckout(['ship_postal_code' => '28001']));

    expect(Order::query()->sole()->shipping_cents)->toBe(790);
});

it('never creates two orders from the same checkout form', function () {
    $variant = cartProduct(stock: 5)->variants->first();
    post('/cesta', ['variant' => $variant->ulid]);

    post('/cesta/confirmar', deliveryCheckout(['checkout_token' => 'tok-1']));
    post('/cesta', ['variant' => $variant->ulid]);
    post('/cesta/confirmar', deliveryCheckout(['checkout_token' => 'tok-1']));

    expect(Order::query()->count())->toBe(1)->and($variant->fresh()->stock)->toBe(4);
});

it('reserves stock, sends the customer to Redsys and waits for the bank', function () {
    post('/cesta', ['variant' => ($variant = cartProduct(2000, stock: 5)->variants->first())->ulid]);
    $response = post('/cesta/confirmar', deliveryCheckout());
    $order = Order::query()->sole();

    $response->assertRedirect(route('payment.start', $order));
    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->payment_expires_at)->not->toBeNull()
        ->and($variant->fresh()->stock)->toBe(4);
    Mail::assertNothingQueued();

    get(route('payment.start', $order))->assertOk()
        ->assertSee('https://sis-t.redsys.es:25443/sis/realizarPago', false)
        ->assertSee('Ds_MerchantParameters', false);

    $payment = Payment::query()->sole();
    $params = RedsysSignature::decodeParameters(
        RedsysSignature::encodeParameters([]) // sanity: helper round-trips
    );
    expect($params)->toBe([])
        ->and($payment->reference)->toMatch('/^\d{12}$/')
        ->and($payment->amount_cents)->toBe(2395);
});

it('marks the order paid from a signed Redsys notification, issues the invoice and e-mails the customer', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    $payment = Payment::query()->sole();

    post('/api/webhooks/redsys', redsysNotification($payment))->assertOk();
    post('/api/webhooks/redsys', redsysNotification($payment))->assertOk(); // retries are harmless

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentAttemptStatus::Paid)
        ->and($payment->fresh()->transaction_id)->toBe('123456')
        ->and($order->invoices()->count())->toBe(1)
        ->and($order->invoices()->first()->type)->toBe(InvoiceType::Simplified)
        ->and($order->invoices()->first()->total_cents)->toBe(2395);

    Mail::assertQueuedCount(1);
    Mail::assertQueued(OrderPlaced::class);
});

it('rejects notifications with a bad signature or the wrong amount', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    $payment = Payment::query()->sole();

    $forged = redsysNotification($payment);
    $forged['Ds_Signature'] = 'AAAA'.substr($forged['Ds_Signature'], 4);
    post('/api/webhooks/redsys', $forged)->assertStatus(400);
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);

    post('/api/webhooks/redsys', redsysNotification($payment, amount: 100))->assertOk();
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending)
        ->and($payment->fresh()->status)->toBe(PaymentAttemptStatus::Failed);
});

it('lets the customer try again after a refused card', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    $first = Payment::query()->sole();

    post('/api/webhooks/redsys', redsysNotification($first, '0190'))->assertOk();
    expect($first->fresh()->status)->toBe(PaymentAttemptStatus::Failed)
        ->and($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    get(route('payment.redsys.return', [$first->ulid, 'ko']))->assertRedirect();

    get(route('payment.start', $order))->assertOk();
    $second = Payment::query()->latest('id')->first();
    expect($second->id)->not->toBe($first->id);

    post('/api/webhooks/redsys', redsysNotification($second))->assertOk();
    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('never marks an order paid from the return page alone', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    $payment = Payment::query()->sole();

    get(route('payment.redsys.return', [$payment->ulid, 'ok']))->assertRedirect();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Pending);
});

it('pays with PayPal: creates the PayPal order, captures it on return and checks the amount', function () {
    Http::fake([
        '*/v1/oauth2/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/v2/checkout/orders' => Http::response(['id' => 'PP-ORDER-1', 'links' => [['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PP-ORDER-1']]], 201),
        '*/v2/checkout/orders/PP-ORDER-1/capture' => Http::response(['id' => 'PP-ORDER-1', 'status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [
            ['id' => 'CAP-1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'EUR', 'value' => '23.95']],
        ]]]]], 201),
    ]);

    $order = onlineOrder(['payment_method' => 'paypal']);
    get(route('payment.start', $order))->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PP-ORDER-1');

    $payment = Payment::query()->sole();
    get(route('payment.paypal.return', [$payment->ulid, 'token' => 'PP-ORDER-1']))->assertRedirect();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->transaction_id)->toBe('CAP-1');
});

it('cancels unpaid online orders when the reservation ends and puts the stock back', function () {
    $order = onlineOrder(stock: 3);
    $variant = $order->items()->first()->variant;
    expect($variant->stock)->toBe(2);

    $this->travel(31 + ReconcilePayments::GRACE_MINUTES)->minutes();
    app(ReconcilePayments::class)->handle();

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->fresh()->cancel_reason)->toBe(ChangeOrderStatus::CANCEL_PAYMENT_EXPIRED)
        ->and($variant->fresh()->stock)->toBe(3);
});

it('refunds through Redsys, issues a corrective invoice and e-mails the customer', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    $payment = Payment::query()->sole();
    post('/api/webhooks/redsys', redsysNotification($payment));

    $answer = RedsysSignature::encodeParameters([
        'Ds_Order' => $payment->reference, 'Ds_MerchantCode' => '999008881', 'Ds_Terminal' => '1',
        'Ds_Response' => '0900', 'Ds_AuthorisationCode' => 'R1', 'Ds_Amount' => '1000',
    ]);
    Http::fake(['*trataPeticionREST' => Http::response([
        'Ds_SignatureVersion' => RedsysSignature::VERSION,
        'Ds_MerchantParameters' => $answer,
        'Ds_Signature' => RedsysSignature::sign($answer, (string) $payment->reference, REDSYS_TEST_KEY),
    ])]);

    app(RefundOrder::class)->handle($order->fresh(), 1000, 'Producto defectuoso');

    $order->refresh();
    $corrective = Invoice::query()->where('type', InvoiceType::Corrective)->sole();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->refunded_cents)->toBe(1000)
        ->and($corrective->total_cents)->toBe(-1000)
        ->and($corrective->corrects_invoice_id)->toBe($order->invoices()->where('type', InvoiceType::Simplified)->value('id'))
        ->and($corrective->code)->toStartWith('R'.now()->year);
    Mail::assertQueued(OrderRefunded::class);

    expect(fn () => app(RefundOrder::class)->handle($order, 5000, 'Demasiado'))->toThrow(DomainRuleException::class);
});

it('numbers invoices one after another and chains their fingerprints', function () {
    foreach (range(1, 3) as $i) {
        post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
        post('/cesta/confirmar', pickupCheckout(['checkout_token' => "p{$i}", 'accept_terms' => '1']));
        $order = Order::query()->latest('id')->first();
        foreach ([OrderStatus::Preparing, OrderStatus::ReadyForPickup, OrderStatus::Completed] as $to) {
            app(ChangeOrderStatus::class)->handle($order->fresh(), $to);
        }
    }

    $invoices = Invoice::query()->orderBy('id')->get();
    $year = now()->year;
    expect($invoices->pluck('code')->all())->toBe(["S{$year}-000001", "S{$year}-000002", "S{$year}-000003"])
        ->and($invoices[1]->previous_hash)->toBe($invoices[0]->hash)
        ->and($invoices[2]->previous_hash)->toBe($invoices[1]->hash);

    expect(fn () => $invoices[0]->update(['total_cents' => 1]))->toThrow(LogicException::class);
});

it('issues a full invoice when the customer gives a NIF', function () {
    $order = onlineOrder(['tax_id' => '12345678Z']);
    get(route('payment.start', $order));
    post('/api/webhooks/redsys', redsysNotification(Payment::query()->sole()));

    $invoice = $order->invoices()->sole();
    expect($invoice->type)->toBe(InvoiceType::Full)->and($invoice->buyer_tax_id)->toBe('12345678Z')
        ->and($invoice->buyer_address)->toContain('Carrer Gran 1');
});

it('lets only the buyer download the invoice PDF', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    post('/api/webhooks/redsys', redsysNotification(Payment::query()->sole()));
    $invoice = $order->invoices()->sole();

    get(route('invoice.download', $invoice))->assertNotFound();
    get(URL::temporarySignedRoute('invoice.download', now()->addHour(), $invoice))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('e-mails the tracking number when staff mark the order shipped', function () {
    $order = onlineOrder();
    get(route('payment.start', $order));
    post('/api/webhooks/redsys', redsysNotification(Payment::query()->sole()));
    $change = app(ChangeOrderStatus::class);
    $change->handle($order->fresh(), OrderStatus::Preparing);
    $change->handle($order->fresh(), OrderStatus::Shipped, shipping: ['carrier' => 'Correos', 'tracking_number' => 'PQ123', 'tracking_url' => 'https://correos.es/t/PQ123']);

    expect($order->fresh()->tracking_number)->toBe('PQ123');
    Mail::assertQueued(OrderShipped::class, fn ($m) => str_contains($m->render(), 'PQ123'));
});

it('lets managers refund from the admin with a password check', function () {
    Filament::setCurrentPanel('admin');
    post('/cesta', ['variant' => cartProduct()->variants->first()->ulid]);
    post('/cesta/confirmar', pickupCheckout(['accept_terms' => '1']));
    $order = Order::query()->sole();
    foreach ([OrderStatus::Preparing, OrderStatus::ReadyForPickup, OrderStatus::Completed] as $to) {
        app(ChangeOrderStatus::class)->handle($order->fresh(), $to);
    }

    actingAs(staffWithRole('Gerente'), Staff::GUARD);
    session([ConfirmPassword::SESSION_KEY => now()->getTimestamp()]);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->callAction('refund', ['amount_cents' => '20.00', 'reason' => 'Cliente insatisfecho'])
        ->assertNotified();

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Refunded);
    expect(staffWithRole('Atención al cliente')->can('orders.refund'))->toBeFalse();
});

it('stores the cookie choice and only shows the banner when tags are configured', function () {
    get('/')->assertDontSee('cookieConsent', false);

    $a = app(AnalyticsSettings::class);
    $a->ga4_measurement_id = 'G-TEST1234';
    $a->save();

    get('/')->assertSee('cookieConsent', false)->assertSee('Rechazar');

    postJson('/cookies/consentimiento', ['analytics' => false, 'marketing' => false])
        ->assertOk()->assertCookie('tm_consent', null, false);

    expect(DB::table('cookie_consents')->count())->toBe(1);
});

it('shows the payment, delivery and cookie settings pages to managers and keeps saved secrets', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole('Gerente'), Staff::GUARD);
    session([ConfirmPassword::SESSION_KEY => now()->getTimestamp()]);

    Livewire::test(ManagePaymentSettings::class)
        ->assertOk()
        ->assertSee(route('webhooks.redsys'))
        ->assertSchemaStateSet(['redsys_secret_key' => null])
        ->fillForm(['redsys_merchant_code' => '123456789'])
        ->call('save')
        ->assertHasNoErrors();

    expect(app(PaymentSettings::class)->redsys_merchant_code)->toBe('123456789')
        ->and(app(PaymentSettings::class)->redsys_secret_key)->toBe(REDSYS_TEST_KEY);

    Livewire::test(ManageCheckoutSettings::class)->assertOk()->assertSee('Entrega local');
    Livewire::test(ManageAnalyticsSettings::class)->assertOk();

    actingAs(staffWithRole('Almacén'), Staff::GUARD);
    expect(ManagePaymentSettings::canAccess())->toBeFalse();
});
