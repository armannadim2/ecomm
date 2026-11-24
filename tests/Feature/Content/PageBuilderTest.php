<?php

use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Settings\StorefrontContentSettings;
use App\Domain\Staff\Models\Staff;
use App\Filament\Pages\ManageStorefrontContent;
use App\Filament\Resources\Pages\Pages\EditPage;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function homeWithBlocks(array $blocks, bool $published = true): Page
{
    return Page::query()->create([
        'title' => 'Inicio', 'slug' => Page::HOME_SLUG, 'type' => Page::TYPE_INFO,
        'is_published' => $published, 'show_in_footer' => false, 'blocks' => $blocks,
    ]);
}

it('shows the default Stitch content when the home page has not been created', function () {
    get('/')->assertOk()
        ->assertSee('Todo para tu hogar y tu día a día en Barcelona')
        ->assertSee('Visítanos en nuestra Tienda Física de Barcelona');
});

it('shows the blocks edited in the admin, in the visitor language with Spanish fallback', function () {
    homeWithBlocks([
        ['type' => 'hero', 'data' => [
            'title' => ['es' => 'Bienvenidos a la tienda', 'ca' => 'Benvinguts a la botiga', 'en' => ''],
            'mosaic' => 'none',
        ]],
    ]);

    get('/')->assertOk()->assertSee('Bienvenidos a la tienda')->assertDontSee('Todo para tu hogar');

    get('/idioma/ca');
    get('/')->assertSee('Benvinguts a la botiga');

    get('/idioma/en');
    get('/')->assertSee('Bienvenidos a la tienda'); // empty English falls back to Spanish
});

it('hides blocks marked as hidden and ignores unknown block types', function () {
    homeWithBlocks([
        ['type' => 'cta', 'data' => ['title' => ['es' => 'Bloque visible'], 'style' => 'navy']],
        ['type' => 'cta', 'data' => ['title' => ['es' => 'Bloque oculto'], 'is_hidden' => true]],
        ['type' => 'script', 'data' => ['title' => ['es' => 'Tipo desconocido']]],
    ]);

    get('/')->assertOk()->assertSee('Bloque visible')->assertDontSee('Bloque oculto')->assertDontSee('Tipo desconocido');
});

it('falls back to the default design when the home page is switched off', function () {
    homeWithBlocks([['type' => 'cta', 'data' => ['title' => ['es' => 'Diseño propio']]]], published: false);

    get('/')->assertOk()->assertDontSee('Diseño propio')->assertSee('Todo para tu hogar y tu día a día en Barcelona');
});

it('escapes block texts and sanitises rich text', function () {
    homeWithBlocks([
        ['type' => 'cta', 'data' => ['title' => ['es' => '<script>alert(1)</script>Hola']]],
        ['type' => 'rich_text', 'data' => ['body' => ['es' => '<p>Texto <strong>seguro</strong></p><script>alert(2)</script>']]],
    ]);

    get('/')->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('<strong>seguro</strong>', false)
        ->assertDontSee('alert(2)', false);
});

it('only follows safe links in blocks', function () {
    homeWithBlocks([
        ['type' => 'cta', 'data' => [
            'title' => ['es' => 'Enlaces'],
            'primary_label' => ['es' => 'Bueno'], 'primary_url' => '/categoria/cocina',
            'secondary_label' => ['es' => 'Malo'], 'secondary_url' => 'javascript:alert(1)',
        ]],
    ]);

    get('/')->assertOk()->assertSee('href="/categoria/cocina"', false)->assertDontSee('javascript:alert', false);
});

it('shows blocks on content pages and redirects the home page slug', function () {
    Page::query()->create([
        'title' => 'Quiénes somos', 'slug' => 'quienes-somos', 'is_published' => true,
        'body' => '<p>Somos un bazar.</p>',
        'blocks' => [['type' => 'cta', 'data' => ['title' => ['es' => 'Ven a vernos']]]],
    ]);
    homeWithBlocks(DefaultContent::home());

    get('/pagina/quienes-somos')->assertOk()->assertSee('Somos un bazar.')->assertSee('Ven a vernos');
    get('/pagina/inicio')->assertRedirect(route('store.home'));
});

it('uses the editable header and footer texts', function () {
    $settings = app(StorefrontContentSettings::class);
    $settings->announcement = ['text' => ['es' => 'Rebajas en toda la tienda', 'ca' => 'Rebaixes a tota la botiga'], 'code' => 'VERANO'];
    $settings->save();

    get('/')->assertOk()->assertSee('Rebajas en toda la tienda')->assertSee('VERANO');
    get('/idioma/ca');
    get('/')->assertSee('Rebaixes a tota la botiga');
});

it('lets content editors open the page builder and the header/footer screen', function () {
    Filament::setCurrentPanel('admin');
    $home = homeWithBlocks(DefaultContent::home());
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(EditPage::class, ['record' => $home->getRouteKey()])->assertOk()->assertSee('Añadir bloque');
    Livewire::test(ManageStorefrontContent::class)->assertOk()->assertSee('Barra superior');
});

it('saves blocks edited in the admin and shows them in the shop', function () {
    Filament::setCurrentPanel('admin');
    $home = homeWithBlocks(DefaultContent::home());
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    $component = Livewire::test(EditPage::class, ['record' => $home->getRouteKey()]);
    $blocks = $component->get('data.blocks');
    $heroKey = collect($blocks)->search(fn ($b) => $b['type'] === 'hero');
    $component
        ->set("data.blocks.{$heroKey}.data.title.ca", 'Tot per a casa teva')
        ->call('save')
        ->assertHasNoFormErrors();

    $saved = collect($home->refresh()->blocks)->firstWhere('type', 'hero');
    expect($saved['data']['title']['ca'])->toBe('Tot per a casa teva')
        ->and($saved['data']['title']['es'])->toBe('Todo para tu hogar y tu día a día en Barcelona');

    get('/idioma/ca');
    get('/')->assertSee('Tot per a casa teva');
});

it('never lets anyone delete the home page', function () {
    $home = homeWithBlocks(DefaultContent::home());

    expect(staffWithRole(Staff::OWNER_ROLE)->can('delete', $home))->toBeFalse();
});
