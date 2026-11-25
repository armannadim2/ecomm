<?php

use App\Domain\Integrations\ApiScopes;
use App\Domain\Integrations\Models\ApiClient;
use App\Domain\Integrations\Models\Webhook;
use App\Domain\Integrations\WebhookEvents;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\ApiClients\ApiClientResource;
use App\Filament\Resources\ApiClients\Pages\ManageApiClients;
use App\Filament\Resources\Webhooks\Pages\ManageWebhooks;
use App\Filament\Resources\Webhooks\WebhookResource;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Filament::setCurrentPanel('admin'));

it('keeps integrations for the owner and staff with the integrations permission', function () {
    actingAs(staffWithRole('Gerente'), Staff::GUARD);
    Livewire::test(ManageApiClients::class)->assertForbidden();
    Livewire::test(ManageWebhooks::class)->assertForbidden();
    expect(ApiClientResource::canViewAny())->toBeFalse()->and(WebhookResource::canViewAny())->toBeFalse();

    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    Livewire::test(ManageApiClients::class)->assertOk()->assertSee('Nueva clave API');
});

it('creates an API key with scopes and shows it only once', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(ManageApiClients::class)
        ->callAction('create', ['name' => 'TPV tienda', 'scopes' => [ApiScopes::PRODUCTS_READ, ApiScopes::STOCK_WRITE], 'current_password' => 'password'])
        ->assertHasNoActionErrors()
        ->assertNotified('Copia la clave ahora: no se volverá a mostrar');

    $client = ApiClient::query()->sole();
    expect($client->scopes())->toBe([ApiScopes::PRODUCTS_READ, ApiScopes::STOCK_WRITE])
        ->and($client->tokens()->sole()->token)->toHaveLength(64); // only the SHA-256 hash is stored
});

it('revokes a key', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $client = ApiClient::query()->create(['name' => 'ERP']);
    $client->issueKey([ApiScopes::PRODUCTS_READ]);

    Livewire::test(ManageApiClients::class)->callTableAction('revoke', $client, ['current_password' => 'password']);

    expect($client->fresh()->isActive())->toBeFalse()->and($client->tokens()->count())->toBe(0);
});

it('creates a webhook with a generated secret and refuses non-web addresses', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(ManageWebhooks::class)
        ->callAction('create', ['name' => 'TPV', 'url' => 'ftp://tpv.example.test', 'events' => [WebhookEvents::ORDER_CREATED], 'is_active' => true, 'current_password' => 'password'])
        ->assertHasActionErrors(['url']);

    Livewire::test(ManageWebhooks::class)
        ->callAction('create', ['name' => 'TPV', 'url' => 'https://tpv.example.test/hook', 'events' => [WebhookEvents::ORDER_CREATED], 'is_active' => true])
        ->assertHasNoActionErrors(); // password confirmed a moment ago: not asked again

    expect(Webhook::query()->sole()->secret)->toStartWith('whsec_');
});

it('asks for the password before sensitive actions and remembers it for 10 minutes', function () {
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $client = ApiClient::query()->create(['name' => 'ERP']);
    $client->issueKey([ApiScopes::PRODUCTS_READ]);

    Livewire::test(ManageApiClients::class)
        ->callTableAction('revoke', $client, ['current_password' => 'no-es-esta'])
        ->assertHasTableActionErrors(['current_password']);
    expect($client->fresh()->isActive())->toBeTrue();

    Livewire::test(ManageApiClients::class)
        ->callTableAction('revoke', $client, ['current_password' => 'password'])
        ->assertHasNoTableActionErrors();
    expect($client->fresh()->isActive())->toBeFalse();

    $this->travel(11)->minutes();
    Livewire::test(ManageApiClients::class)
        ->callAction('create', ['name' => 'Otra', 'scopes' => [ApiScopes::PRODUCTS_READ]])
        ->assertHasActionErrors(['current_password']);
});
