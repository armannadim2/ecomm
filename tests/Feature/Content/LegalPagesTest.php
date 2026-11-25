<?php

use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;

use function Pest\Laravel\artisan;
use function Pest\Laravel\get;

it('rewrites and publishes the legal pages with the shop data filled in', function () {
    $store = app(StoreSettings::class);
    $store->legal_name = 'Tot i Més Comerç S.L.';
    $store->nif = 'B00000000';
    $store->save();

    artisan('legal:refresh', ['--publish' => true, '--force' => true])->assertSuccessful();

    expect(Page::query()->where('type', Page::TYPE_LEGAL)->published()->count())->toBe(5);

    get('/pagina/aviso-legal')->assertOk()
        ->assertSee('Tot i Més Comerç S.L.')
        ->assertSee('B00000000')
        ->assertSee('Avinguda Meridiana 507')
        ->assertDontSee('[nif]')
        ->assertDontSee('Plantilla orientativa');

    get('/pagina/condiciones-generales')->assertOk()
        ->assertSee('14 días naturales')
        ->assertSee('3 años')
        ->assertSee('10:00 - 21:00'); // [horario] from Envíos y pagos

    get('/pagina/politica-de-cookies')->assertOk()->assertSee('XSRF-TOKEN')->assertSee('<table>', false);
    get('/pagina/envios-y-devoluciones')->assertOk()->assertSee('Modelo de formulario de desistimiento');
});

it('warns when the legal name or NIF are still missing', function () {
    artisan('legal:refresh', ['--force' => true])
        ->expectsOutputToContain('Falta el nombre legal o el NIF')
        ->assertSuccessful();
});
