<?php

use App\Domain\Marketing\Enums\CampaignEffect;
use App\Domain\Marketing\Enums\CampaignTheme;
use App\Domain\Marketing\Models\Campaign;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows the live campaign with its effect on the home page', function () {
    Campaign::factory()->create(['headline' => 'Black Friday Total', 'effect' => CampaignEffect::Snow]);

    get('/')->assertOk()
        ->assertSee('Black Friday Total')
        ->assertSee('data-campaign-effect="snow"', false)
        ->assertSee('data-countdown=', false);
});

it('runs the effect on the home page only', function () {
    Campaign::factory()->create(['effect' => CampaignEffect::Snow]);

    get('/buscar')->assertOk()->assertDontSee('data-campaign-effect', false);
});

it('ignores upcoming, ended and deactivated campaigns', function () {
    Campaign::factory()->upcoming()->create(['headline' => 'Futura']);
    Campaign::factory()->ended()->create(['headline' => 'Pasada']);
    Campaign::factory()->create(['headline' => 'Pausada', 'is_active' => false]);

    get('/')->assertOk()
        ->assertDontSee('Futura')
        ->assertDontSee('Pasada')
        ->assertDontSee('Pausada')
        ->assertDontSee('data-campaign-effect', false);
});

it('shows the highest-priority campaign when two overlap', function () {
    Campaign::factory()->create(['headline' => 'Normal', 'priority' => 0]);
    Campaign::factory()->create(['headline' => 'Importante', 'priority' => 10]);

    expect(Campaign::current()->headline)->toBe('Importante');
});

it('gives every theme sensible defaults', function (CampaignTheme $theme) {
    $defaults = $theme->defaults();

    expect($defaults['effect'])->toBeInstanceOf(CampaignEffect::class)
        ->and($defaults['accent_color'])->toMatch('/^#[0-9a-f]{6}$/i');
})->with(CampaignTheme::cases());

it('pre-fills the form when a campaign type is chosen', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(CreateCampaign::class)
        ->fillForm(['theme' => CampaignTheme::Navidad->value])
        ->assertSchemaStateSet([
            'effect' => CampaignEffect::Snow,
            'headline' => 'Navidad',
        ]);
});

it('lets content staff manage campaigns but not warehouse staff', function () {
    Filament::setCurrentPanel('admin');

    actingAs(staffWithRole('Contenido'), Staff::GUARD);
    Livewire::test(ListCampaigns::class)->assertOk();

    expect(staffWithRole('Almacén')->can('viewAny', Campaign::class))->toBeFalse();
});
