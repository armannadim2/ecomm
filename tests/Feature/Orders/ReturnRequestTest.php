<?php

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Actions\ChangeReturnStatus;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Mail\ReturnRequestReceived;
use App\Domain\Orders\Mail\ReturnRequestStatusChanged;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Returns\Pages\ListReturnRequests;
use App\Filament\Resources\Returns\Pages\ViewReturnRequest;
use App\Filament\Support\ConfirmPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

/** A delivered order with two lines (2 × 25,00 € and 1 × 10,00 €), delivered `$deliveredDaysAgo` days ago. */
function deliveredOrder(Customer $customer, int $deliveredDaysAgo = 3, OrderStatus $status = OrderStatus::Completed): Order
{
    $order = Order::query()->create([
        'number' => 'TM-TEST-'.fake()->unique()->numerify('#####'),
        'customer_id' => $customer->id,
        'status' => $status,
        'fulfillment' => Fulfillment::Pickup,
        'payment_method' => PaymentMethod::PayInStore,
        'payment_status' => PaymentStatus::Paid,
        'first_name' => $customer->first_name, 'last_name' => $customer->last_name,
        'email' => $customer->email, 'phone' => '612345678',
        'subtotal_cents' => 6000, 'total_cents' => 6000, 'tax_cents' => 1041,
        'locale' => 'es',
        'placed_at' => now()->subDays($deliveredDaysAgo + 1),
        'completed_at' => $status === OrderStatus::Completed ? now()->subDays($deliveredDaysAgo) : null,
    ]);
    $order->items()->create(['product_name' => 'Joc de paelles', 'variant_name' => '24 cm', 'sku' => 'PAE-24', 'unit_price_cents' => 2500, 'quantity' => 2, 'tax_rate' => 21, 'line_total_cents' => 5000]);
    $order->items()->create(['product_name' => 'Set d\'estris', 'sku' => 'UTE-5', 'unit_price_cents' => 1000, 'quantity' => 1, 'tax_rate' => 21, 'line_total_cents' => 1000]);

    return $order->load('items');
}

/** Form data to return `$units` of the first line and nothing else. */
function returnForm(Order $order, array $overrides = []): array
{
    return [
        'order' => $order->ulid,
        'type' => 'return',
        'reason' => 'changed_mind',
        'comment' => 'No me cabe en los fogones.',
        'quantities' => [$order->items[0]->id => 1],
        'accept' => '1',
        ...$overrides,
    ];
}

beforeEach(function () {
    Mail::fake();
    $this->customer = Customer::factory()->create(['email' => 'marta@example.test']);
    actingAs($this->customer, 'web');
});

describe('customer account', function () {
    it('shows the returns link in the account menu and the list with the shop conditions', function () {
        get('/cuenta')->assertOk()->assertSee(route('account.returns'), false)->assertSee('Devoluciones y cambios');

        get(route('account.returns'))
            ->assertOk()
            ->assertSee('14 días para devolver')
            ->assertSee('Garantía legal de 3 años')
            ->assertSee('Avinguda Meridiana 507')
            ->assertDontSee('recogida a domicilio');
    });

    it('only offers delivered orders of the customer', function () {
        $mine = deliveredOrder($this->customer);
        $open = deliveredOrder($this->customer, status: OrderStatus::Preparing);
        $other = deliveredOrder(Customer::factory()->create());

        get(route('account.returns.create'))
            ->assertOk()
            ->assertSee($mine->number)
            ->assertDontSee($open->number)
            ->assertDontSee($other->number);
    });

    it('says so when there is nothing to return', function () {
        deliveredOrder($this->customer, status: OrderStatus::Preparing);

        get(route('account.returns.create'))->assertOk()->assertSee('No tienes pedidos entregados');
    });

    it('requires a verified customer', function () {
        actingAs(Customer::factory()->unverified()->create(), 'web');

        get(route('account.returns'))->assertRedirect(route('verification.notice'));
    });
});

describe('creating a request', function () {
    it('saves the request with its items and e-mails the customer', function () {
        $order = deliveredOrder($this->customer);

        post(route('account.returns.store'), returnForm($order))
            ->assertRedirect()
            ->assertSessionHas('status');

        $request = ReturnRequest::query()->with('items')->sole();
        expect($request->status)->toBe(ReturnStatus::Requested)
            ->and($request->number)->toMatch('/^DEV-\d{4}-\d{5}$/')
            ->and($request->customer_id)->toBe($this->customer->id)
            ->and($request->order_id)->toBe($order->id)
            ->and($request->refund_cents)->toBe(2500)
            ->and($request->items)->toHaveCount(1)
            ->and($request->items[0]->product_name)->toBe('Joc de paelles')
            ->and($request->items[0]->quantity)->toBe(1);

        Mail::assertQueued(ReturnRequestReceived::class, fn ($mail) => $mail->hasTo('marta@example.test'));

        get(route('account.returns.show', $request))->assertOk()->assertSee($request->number)->assertSee('Joc de paelles');
        get(route('account.returns'))->assertSee($request->number);
    });

    it('accepts an exchange of several items', function () {
        $order = deliveredOrder($this->customer);

        post(route('account.returns.store'), returnForm($order, [
            'type' => 'exchange', 'reason' => 'size_fit',
            'quantities' => [$order->items[0]->id => 2, $order->items[1]->id => 1],
        ]))->assertRedirect();

        $request = ReturnRequest::query()->sole();
        expect($request->type->value)->toBe('exchange')->and($request->refund_cents)->toBe(6000)->and($request->itemCount())->toBe(3);
    });

    it('rejects a request with no items, or without confirming the conditions', function () {
        $order = deliveredOrder($this->customer);

        post(route('account.returns.store'), returnForm($order, ['quantities' => [$order->items[0]->id => 0]]))
            ->assertSessionHasErrors('request');
        post(route('account.returns.store'), returnForm($order, ['accept' => null]))
            ->assertSessionHasErrors('accept');
        post(route('account.returns.store'), returnForm($order, ['reason' => 'nope']))
            ->assertSessionHasErrors('reason');

        expect(ReturnRequest::query()->count())->toBe(0);
        Mail::assertNothingQueued();
    });

    it('does not let the same units be returned twice, but frees them if staff reject the request', function () {
        $order = deliveredOrder($this->customer);
        post(route('account.returns.store'), returnForm($order, ['quantities' => [$order->items[0]->id => 2]]))->assertRedirect();

        post(route('account.returns.store'), returnForm($order))->assertSessionHasErrors('request');
        expect(ReturnRequest::query()->count())->toBe(1);

        app(ChangeReturnStatus::class)->handle(ReturnRequest::query()->sole(), ReturnStatus::Rejected, 'Está usado.');

        post(route('account.returns.store'), returnForm($order))->assertRedirect();
        expect(ReturnRequest::query()->count())->toBe(2);
    });

    it('cannot return more units than were ordered', function () {
        $order = deliveredOrder($this->customer);

        post(route('account.returns.store'), returnForm($order, ['quantities' => [$order->items[0]->id => 3]]))
            ->assertSessionHasErrors('request');
    });

    it('cannot return an order that is not delivered', function () {
        $order = deliveredOrder($this->customer, status: OrderStatus::ReadyForPickup);

        post(route('account.returns.store'), returnForm($order))->assertSessionHasErrors('request');
        expect(ReturnRequest::query()->count())->toBe(0);
    });

    it('cannot use another customer\'s order', function () {
        $order = deliveredOrder(Customer::factory()->create());

        post(route('account.returns.store'), returnForm($order))->assertNotFound();
        expect(ReturnRequest::query()->count())->toBe(0);
    });
});

describe('return window', function () {
    it('allows a return without reason on day 14 and refuses it from day 15', function () {
        $onTime = deliveredOrder($this->customer, 14);
        post(route('account.returns.store'), returnForm($onTime))->assertRedirect();

        $late = deliveredOrder($this->customer, 15);
        post(route('account.returns.store'), returnForm($late))->assertSessionHasErrors('request');

        expect(ReturnRequest::query()->count())->toBe(1);
    });

    it('still accepts a defective or wrong product after 14 days, under the 3-year guarantee', function () {
        $order = deliveredOrder($this->customer, 400);

        post(route('account.returns.store'), returnForm($order, ['reason' => 'defective']))->assertRedirect();
        post(route('account.returns.store'), returnForm($order, ['reason' => 'wrong_item', 'quantities' => [$order->items[1]->id => 1]]))->assertRedirect();

        expect(ReturnRequest::query()->count())->toBe(2);
        get(route('account.returns.create', ['order' => $order->ulid]))->assertSee('solo puedes reclamar la garantía legal');
    });

    it('refuses everything once the 3-year guarantee has passed, and hides the order', function () {
        $order = deliveredOrder($this->customer, 365 * 3 + 5);

        post(route('account.returns.store'), returnForm($order, ['reason' => 'defective']))->assertSessionHasErrors('request');
        get(route('account.returns.create'))->assertDontSee($order->number);
    });
});

describe('privacy', function () {
    it('shows a request only to its owner', function () {
        $order = deliveredOrder($this->customer);
        post(route('account.returns.store'), returnForm($order));
        $request = ReturnRequest::query()->sole();

        actingAs(Customer::factory()->create(), 'web');
        get(route('account.returns.show', $request))->assertNotFound();
        get(route('account.returns'))->assertDontSee($request->number);
    });
});

describe('status changes', function () {
    function pendingReturn(Customer $customer, string $type = 'return'): ReturnRequest
    {
        $order = deliveredOrder($customer);
        post(route('account.returns.store'), returnForm($order, ['type' => $type]));

        return ReturnRequest::query()->latest('id')->firstOrFail();
    }

    it('walks a return through approved, received and refunded, e-mailing the customer each time', function () {
        $request = pendingReturn($this->customer);
        $change = app(ChangeReturnStatus::class);

        $change->handle($request, ReturnStatus::Approved);
        $change->handle($request, ReturnStatus::Received);
        $change->handle($request, ReturnStatus::Refunded);

        expect($request->fresh())
            ->status->toBe(ReturnStatus::Refunded)
            ->approved_at->not->toBeNull()
            ->received_at->not->toBeNull()
            ->resolved_at->not->toBeNull();
        Mail::assertQueued(ReturnRequestStatusChanged::class, 3);
        Mail::assertQueued(ReturnRequestStatusChanged::class, fn ($mail) => $mail->hasTo('marta@example.test') && $mail->returnRequest->status === ReturnStatus::Refunded);
    });

    it('ends an exchange as exchanged, not refunded', function () {
        $request = pendingReturn($this->customer, 'exchange');
        $change = app(ChangeReturnStatus::class);
        $change->handle($request, ReturnStatus::Approved);
        $change->handle($request, ReturnStatus::Received);

        expect(fn () => $change->handle($request, ReturnStatus::Refunded))->toThrow(DomainRuleException::class);
        $change->handle($request, ReturnStatus::Exchanged);

        expect($request->fresh()->status)->toBe(ReturnStatus::Exchanged);
    });

    it('needs a reason to reject and cannot skip steps', function () {
        $request = pendingReturn($this->customer);
        $change = app(ChangeReturnStatus::class);

        expect(fn () => $change->handle($request, ReturnStatus::Rejected))->toThrow(DomainRuleException::class)
            ->and(fn () => $change->handle($request, ReturnStatus::Refunded))->toThrow(DomainRuleException::class);

        $change->handle($request, ReturnStatus::Rejected, 'El producto está usado.');
        expect($request->fresh()->rejection_reason)->toBe('El producto está usado.');
        expect(fn () => $change->handle($request->fresh(), ReturnStatus::Approved))->toThrow(DomainRuleException::class);
    });

    it('writes the e-mails in the customer\'s language, with the shop data', function () {
        $request = pendingReturn($this->customer);
        $request->order->update(['locale' => 'ca']);
        $request->update(['status' => ReturnStatus::Approved, 'locale' => 'ca']);

        $html = (new ReturnRequestStatusChanged($request->fresh()))->locale('ca')->render();

        expect($html)
            ->toContain($request->number)
            ->toContain('Joc de paelles')
            ->toContain('Avinguda Meridiana 507')
            ->toContain('Ja pots portar el producte a la botiga')
            ->not->toContain('Ya puedes traer');

        $created = (new ReturnRequestReceived($request->fresh()))->render();
        expect($created)->toContain('Hemos recibido tu solicitud')->toContain($request->number);
    });
});

describe('staff', function () {
    it('lets order staff approve, reject and complete requests from the admin', function () {
        $request = pendingReturn($this->customer);
        $second = pendingReturn($this->customer);
        Filament::setCurrentPanel('admin');
        actingAs(staffWithRole('Pedidos'), Staff::GUARD);
        session([ConfirmPassword::SESSION_KEY => now()->getTimestamp()]);

        Livewire::test(ViewReturnRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('to_approved')
            ->assertNotified();
        expect($request->fresh()->status)->toBe(ReturnStatus::Approved);

        Livewire::test(ViewReturnRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('to_received');
        Livewire::test(ViewReturnRequest::class, ['record' => $request->getRouteKey()])
            ->callAction('to_refunded', ['amount_cents' => '50.00']);
        expect($request->fresh()->status)->toBe(ReturnStatus::Refunded)
            ->and($request->order->fresh()->refunded_cents)->toBe(5000)
            ->and($request->order->invoices()->where('type', 'corrective')->value('total_cents'))->toBe(-5000);

        Livewire::test(ViewReturnRequest::class, ['record' => $second->getRouteKey()])
            ->callAction('to_rejected', ['rejection_reason' => 'Fuera de plazo.']);
        expect($second->fresh())->status->toBe(ReturnStatus::Rejected)->rejection_reason->toBe('Fuera de plazo.');
    });

    it('gives view access to customer service but not the right to change requests', function () {
        $request = pendingReturn($this->customer);
        Filament::setCurrentPanel('admin');

        $viewer = staffWithRole('Atención al cliente');
        expect($viewer->can('view', $request))->toBeTrue()->and($viewer->can('update', $request))->toBeFalse();

        actingAs($viewer, Staff::GUARD);
        Livewire::test(ViewReturnRequest::class, ['record' => $request->getRouteKey()])
            ->assertActionHidden('to_approved');

        $warehouse = staffWithRole('Almacén');
        expect($warehouse->can('viewAny', ReturnRequest::class))->toBeFalse();
        actingAs($warehouse, Staff::GUARD);
        Livewire::test(ListReturnRequests::class)->assertForbidden();
    });

    it('lists the requests in the admin for staff who can see them', function () {
        $request = pendingReturn($this->customer);
        Filament::setCurrentPanel('admin');
        actingAs(staffWithRole('Gerente'), Staff::GUARD);

        Livewire::test(ListReturnRequests::class)->assertOk()->assertCanSeeTableRecords([$request]);
        Livewire::test(ViewReturnRequest::class, ['record' => $request->getRouteKey()])->assertOk()->assertSee('Joc de paelles');
    });
});
