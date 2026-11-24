<?php

use App\Domain\Customers\Models\Address;
use App\Domain\Customers\Models\Customer;
use App\Domain\Staff\Models\Staff;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

function validRegistration(array $overrides = []): array
{
    return [
        'first_name' => 'Lucía',
        'last_name' => 'García',
        'email' => 'Lucia@Example.com',
        'password' => 'contraseña-segura-1',
        'password_confirmation' => 'contraseña-segura-1',
        'accept_terms' => '1',
        ...$overrides,
    ];
}

describe('registration', function () {
    it('creates an account, records consent and asks to verify the email', function () {
        Notification::fake();

        post('/cuenta/registro', validRegistration(['marketing' => '1']))
            ->assertRedirect(route('verification.notice'));

        $customer = Customer::query()->where('email', 'lucia@example.com')->firstOrFail();
        expect($customer->terms_accepted_at)->not->toBeNull()
            ->and($customer->marketing_consent_at)->not->toBeNull()
            ->and($customer->email_verified_at)->toBeNull();

        Notification::assertSentTo($customer, VerifyEmail::class);
    });

    it('does not opt in to marketing unless the box is ticked', function () {
        post('/cuenta/registro', validRegistration());

        expect(Customer::query()->first()->marketing_consent_at)->toBeNull();
    });

    it('requires accepting the terms', function () {
        post('/cuenta/registro', validRegistration(['accept_terms' => null]))->assertSessionHasErrors('accept_terms');

        expect(Customer::query()->count())->toBe(0);
    });

    it('rejects an email that is already registered', function () {
        Customer::factory()->create(['email' => 'lucia@example.com']);

        post('/cuenta/registro', validRegistration())->assertSessionHasErrors('email');
    });
});

describe('login', function () {
    it('logs in and out', function () {
        $customer = Customer::factory()->create(['email' => 'ana@example.com']);

        post('/cuenta/acceder', ['email' => 'ANA@example.com', 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));
        assertAuthenticatedAs($customer, 'web');

        post('/cuenta/salir')->assertRedirect(route('store.home'));
        assertGuest('web');
    });

    it('gives the same error for a wrong password or an unknown email', function () {
        Customer::factory()->create(['email' => 'ana@example.com']);

        post('/cuenta/acceder', ['email' => 'ana@example.com', 'password' => 'mal'])
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
        post('/cuenta/acceder', ['email' => 'nadie@example.com', 'password' => 'mal'])
            ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);
    });

    it('locks out after 5 failed attempts', function () {
        Customer::factory()->create(['email' => 'ana@example.com']);

        foreach (range(1, 5) as $i) {
            post('/cuenta/acceder', ['email' => 'ana@example.com', 'password' => 'mal']);
        }

        post('/cuenta/acceder', ['email' => 'ana@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        assertGuest('web');
    });

    it('does not let staff log in to the storefront', function () {
        Staff::factory()->create(['email' => 'staff@example.com']);

        post('/cuenta/acceder', ['email' => 'staff@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
    });
});

describe('verification and password reset', function () {
    it('verifies the email from the signed link', function () {
        $customer = Customer::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $customer->id, 'hash' => sha1($customer->email),
        ]);

        actingAs($customer, 'web')->get($url)->assertRedirect(route('account.dashboard'));

        expect($customer->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('keeps unverified customers out of the account area', function () {
        actingAs(Customer::factory()->unverified()->create(), 'web')
            ->get('/cuenta/direcciones')->assertRedirect(route('verification.notice'));
    });

    it('sends a reset link without revealing whether the email exists', function () {
        Notification::fake();
        $customer = Customer::factory()->create(['email' => 'ana@example.com']);

        post('/cuenta/recuperar', ['email' => 'ana@example.com'])->assertSessionHas('status');
        post('/cuenta/recuperar', ['email' => 'nadie@example.com'])->assertSessionHas('status');

        Notification::assertSentTo($customer, ResetPassword::class);
    });
});

describe('addresses', function () {
    it('fills the province from the postal code and makes the first address the default', function () {
        $customer = Customer::factory()->create();

        actingAs($customer, 'web')->post('/cuenta/direcciones', [
            'first_name' => 'Ana', 'last_name' => 'López', 'line1' => 'Calle Mayor 1',
            'postal_code' => '08001', 'city' => 'Barcelona', 'phone' => '600 123 456',
        ])->assertRedirect(route('account.addresses'));

        expect($customer->addresses()->first())
            ->province->toBe('Barcelona')
            ->is_default->toBeTrue();
    });

    it('refuses addresses in Canarias, Ceuta and Melilla for now', function () {
        actingAs(Customer::factory()->create(), 'web')->post('/cuenta/direcciones', [
            'first_name' => 'Ana', 'last_name' => 'López', 'line1' => 'Calle 1',
            'postal_code' => '35001', 'city' => 'Las Palmas', 'phone' => '600123456',
        ])->assertSessionHasErrors(['postal_code' => 'De momento solo enviamos a la Península y Baleares.']);
    });

    it('never lets a customer see or change another customer address', function () {
        $someoneElses = Address::factory()->create();

        actingAs(Customer::factory()->create(), 'web');

        get("/cuenta/direcciones/{$someoneElses->ulid}")->assertNotFound();
        put("/cuenta/direcciones/{$someoneElses->ulid}", [])->assertSessionHasErrors();
        delete("/cuenta/direcciones/{$someoneElses->ulid}")->assertNotFound();

        expect($someoneElses->fresh())->not->toBeNull();
    });

    it('keeps exactly one default address', function () {
        $customer = Customer::factory()->create();
        $first = Address::factory()->for($customer)->create(['is_default' => true]);
        $second = Address::factory()->for($customer)->create();

        actingAs($customer, 'web')->put("/cuenta/direcciones/{$second->ulid}", [
            ...$second->only(['first_name', 'last_name', 'line1', 'postal_code', 'city', 'phone']),
            'is_default' => '1',
        ])->assertRedirect();

        expect($first->fresh()->is_default)->toBeFalse()
            ->and($second->fresh()->is_default)->toBeTrue();
    });
});

describe('privacy (GDPR)', function () {
    it('downloads the customer data as JSON', function () {
        $customer = Customer::factory()->create(['email' => 'ana@example.com']);
        Address::factory()->for($customer)->create(['city' => 'Sevilla']);

        $response = actingAs($customer, 'web')->post('/cuenta/privacidad/exportar')->assertOk();

        expect($response->streamedContent())->toContain('ana@example.com')->toContain('Sevilla');
    });

    it('deletes and anonymises the account after password confirmation', function () {
        $customer = Customer::factory()->create(['email' => 'ana@example.com', 'phone' => '600000000']);
        Address::factory()->for($customer)->create();

        actingAs($customer, 'web')->delete('/cuenta', ['password' => 'mal'])->assertSessionHasErrors('password');
        expect($customer->fresh()->trashed())->toBeFalse();

        delete('/cuenta', ['password' => 'password'])->assertRedirect(route('store.home'));

        $deleted = Customer::withTrashed()->find($customer->id);
        expect($deleted->trashed())->toBeTrue()
            ->and($deleted->email)->not->toBe('ana@example.com')
            ->and($deleted->phone)->toBeNull()
            ->and($deleted->anonymized_at)->not->toBeNull()
            ->and($deleted->addresses()->count())->toBe(0);
        assertGuest('web');
    });
});
