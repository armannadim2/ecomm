<?php

use App\Domain\Content\Models\Page;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Pages\Pages\VisualEditor;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function editorHome(): Page
{
    return Page::query()->create([
        'title' => 'Inicio', 'slug' => Page::HOME_SLUG, 'type' => Page::TYPE_INFO,
        'is_published' => true, 'show_in_footer' => false,
        'blocks' => [
            ['type' => 'cta', 'data' => ['title' => ['es' => 'Primero', 'ca' => 'Primer']]],
            ['type' => 'cta', 'data' => ['title' => ['es' => 'Segundo']]],
        ],
    ]);
}

function openEditor(Page $page)
{
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    return Livewire::test(VisualEditor::class, ['record' => $page->getRouteKey()]);
}

it('keeps the editor preview for signed-in staff only', function () {
    $home = editorHome();

    get(route('admin.page-editor.preview', $home))->assertRedirect();

    actingAs(staffWithRole('Almacén'), Staff::GUARD);
    get(route('admin.page-editor.preview', $home))->assertForbidden();
});

it('renders the preview with editing markers and without search indexing', function () {
    $home = editorHome();
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    get(route('admin.page-editor.preview', ['page' => $home, 'locale' => 'ca']))
        ->assertOk()
        ->assertHeader('X-Robots-Tag')
        ->assertSee('data-pb-index="1"', false)
        ->assertSee('data-pb-edit="0.title"', false)
        ->assertSee('Primer');
});

it('edits texts in the chosen language without touching the shop until publishing', function () {
    $home = editorHome();

    openEditor($home)
        ->call('updateText', '0.title', 'Hola <b>mundo</b>')
        ->call('setLocale', 'ca')
        ->call('updateText', '1.title', 'Segon')
        ->assertSet('blocks.0.data.title.es', 'Hola mundo')
        ->assertSet('blocks.1.data.title.ca', 'Segon')
        ->assertSet('blocks.1.data.title.es', 'Segundo')
        ->assertSet('hasChanges', true);

    expect($home->fresh()->blocks[0]['data']['title']['es'])->toBe('Primero');
});

it('ignores text paths that do not point to a block field', function () {
    $home = editorHome();

    openEditor($home)
        ->call('updateText', '9.title', 'x')
        ->call('updateText', '0.title.es.extra', 'x')
        ->call('updateText', '../0.title', 'x')
        ->assertSet('blocks.0.data.title.es', 'Primero')
        ->assertSet('canUndo', false);
});

it('adds, reorders, duplicates and removes sections, with undo and redo', function () {
    $home = editorHome();

    $editor = openEditor($home)
        ->call('insert', 'rich_text', 0)
        ->assertSet('blocks.0.type', 'rich_text')
        ->assertSet('selected', 0)
        ->call('insert', 'not_a_block')
        ->assertCount('blocks', 3)
        ->call('reorder', [2, 1, 0])
        ->assertSet('blocks.0.data.title.es', 'Segundo')
        ->assertSet('blocks.2.type', 'rich_text')
        ->call('duplicate', 0)
        ->assertCount('blocks', 4)
        ->call('remove', 0)
        ->assertCount('blocks', 3)
        ->call('undo')
        ->assertCount('blocks', 4)
        ->call('redo')
        ->assertCount('blocks', 3);

    // A reorder that does not list every section is refused.
    $editor->call('reorder', [0, 0, 1])->assertSet('blocks.2.type', 'rich_text');
});

it('saves a draft, then publishes it to the shop', function () {
    $home = editorHome();

    $editor = openEditor($home)
        ->call('updateText', '0.title', 'Nuevo título')
        ->call('saveDraft')
        ->assertSet('hasChanges', false);

    expect($home->fresh())
        ->draft_blocks->{0}->data->title->es->toBe('Nuevo título')
        ->blocks->{0}->data->title->es->toBe('Primero');

    get('/')->assertDontSee('Nuevo título');

    $editor->call('publish');

    expect($home->fresh())->draft_blocks->toBeNull()
        ->blocks->{0}->data->title->es->toBe('Nuevo título');

    get('/')->assertSee('Nuevo título');
});

it('discards unpublished changes', function () {
    $home = editorHome();

    openEditor($home)
        ->call('updateText', '0.title', 'Cambio')
        ->call('saveDraft')
        ->call('discard')
        ->assertSet('blocks.0.data.title.es', 'Primero')
        ->assertSet('hasChanges', false);

    expect($home->fresh()->draft_blocks)->toBeNull();
});
