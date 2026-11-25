<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Domain\Content\Blocks\BlockTypes;
use App\Domain\Content\Models\Page;
use App\Filament\Resources\Pages\PageResource;
use App\Support\PageEditorDraft;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

/**
 * Visual page editor (Elementor-style): live preview of the real page in the centre,
 * section library / structure / settings on the left, click-to-type texts, drag & drop.
 *
 * The working copy lives in the cache per staff member (PageEditorDraft), so the preview
 * iframe always shows unsaved changes. "Guardar borrador" stores it in pages.draft_blocks;
 * "Publicar" copies it to pages.blocks (what the shop shows). Undo/redo history is cached too.
 *
 * @property Page $record
 */
class VisualEditor extends ResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = PageResource::class;

    protected string $view = 'filament.pages.visual-editor';

    private const HISTORY_LIMIT = 40;

    /** @var list<array{type: string, data: array<string, mixed>}> */
    public array $blocks = [];

    public ?int $selected = null;

    public string $locale = 'es';

    /** Re-keys the side panel so it reloads the block data. */
    public int $panelVersion = 0;

    /** Unsaved changes compared to the last saved draft or the published page. */
    public bool $hasChanges = false;

    public bool $canUndo = false;

    public bool $canRedo = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(auth()->user()->can('update', $this->record), 403);

        $this->blocks = PageEditorDraft::get($this->record, auth()->user());
        $this->hasChanges = $this->blocks !== PageEditorDraft::saved($this->record);
        $this->syncHistoryFlags();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editor visual · '.$this->record->title;
    }

    /* ---------------- Selection & language ---------------- */

    public function select(int $index, bool $keepPanel = false): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        if ($this->selected !== $index || ! $keepPanel) {
            $this->panelVersion++;
        }

        $this->selected = $index;
        $this->dispatch('pb-selected', index: $index);
    }

    public function deselect(): void
    {
        $this->selected = null;
        $this->dispatch('pb-selected', index: null);
    }

    public function setLocale(string $locale): void
    {
        if (array_key_exists($locale, config('app.available_locales'))) {
            $this->locale = $locale;
            $this->dispatch('pb-refresh');
        }
    }

    /* ---------------- Block operations ---------------- */

    public function insert(string $type, ?int $at = null): void
    {
        if (! in_array($type, BlockTypes::TYPES, true)) {
            return;
        }

        $at ??= $this->selected !== null ? $this->selected + 1 : count($this->blocks);
        $at = max(0, min($at, count($this->blocks)));

        $this->change(function (array $blocks) use ($type, $at) {
            array_splice($blocks, $at, 0, [['type' => $type, 'data' => BlockTypes::defaults($type)]]);

            return $blocks;
        });

        $this->select($at);
    }

    public function move(int $index, int $to): void
    {
        if (! isset($this->blocks[$index], $this->blocks[$to]) || $index === $to) {
            return;
        }

        $this->change(function (array $blocks) use ($index, $to) {
            $item = array_splice($blocks, $index, 1)[0];
            array_splice($blocks, $to, 0, [$item]);

            return $blocks;
        });

        $this->selected = $to;
        $this->dispatch('pb-selected', index: $to);
    }

    /** @param  list<int>  $order  Old positions in their new order (from drag & drop). */
    public function reorder(array $order): void
    {
        $order = array_map('intval', $order);
        $current = array_keys($this->blocks);

        if (count($order) !== count($current) || array_diff($current, $order) !== []) {
            $this->dispatch('pb-refresh');

            return;
        }

        $selectedBlock = $this->selected;
        $this->change(fn (array $blocks) => array_map(fn (int $old) => $blocks[$old], $order));

        if ($selectedBlock !== null) {
            $this->selected = array_search($selectedBlock, $order, true) ?: 0;
            $this->dispatch('pb-selected', index: $this->selected);
        }
    }

    public function duplicate(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $this->change(function (array $blocks) use ($index) {
            array_splice($blocks, $index + 1, 0, [$blocks[$index]]);

            return $blocks;
        });

        $this->select($index + 1);
    }

    public function toggle(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $this->change(function (array $blocks) use ($index) {
            $blocks[$index]['data']['is_hidden'] = empty($blocks[$index]['data']['is_hidden']);

            return $blocks;
        });

        $this->panelVersion++;
    }

    public function remove(int $index): void
    {
        if (! isset($this->blocks[$index])) {
            return;
        }

        $this->change(function (array $blocks) use ($index) {
            array_splice($blocks, $index, 1);

            return $blocks;
        });

        $this->deselect();
    }

    /** Click-to-type text from the preview. Path: "3.title" or "3.cards.0.title". */
    public function updateText(string $path, string $value): void
    {
        if (! preg_match('/^(\d+)\.([a-z_]+(?:\.\d+\.[a-z_]+)?)$/', $path, $m) || ! isset($this->blocks[(int) $m[1]])) {
            return;
        }

        [$index, $key] = [(int) $m[1], $m[2]];
        $value = Str::limit(trim(strip_tags($value)), 500, '');

        $this->change(function (array $blocks) use ($index, $key, $value) {
            $current = data_get($blocks[$index]['data'], $key);

            if (is_array($current) || $current === null) {
                data_set($blocks[$index]['data'], $key.'.'.$this->locale, $value);
            } else {
                data_set($blocks[$index]['data'], $key, $value);
            }

            return $blocks;
        }, refresh: false);

        $this->panelVersion++;
    }

    /** New data from the side panel for the selected block. */
    #[On('pb-block-data')]
    public function applyPanel(array $data): void
    {
        if ($this->selected === null || ! isset($this->blocks[$this->selected])) {
            return;
        }

        if ($this->blocks[$this->selected]['data'] === $data) {
            return;
        }

        $index = $this->selected;
        $this->change(function (array $blocks) use ($index, $data) {
            $blocks[$index]['data'] = $data;

            return $blocks;
        });
    }

    /* ---------------- History ---------------- */

    public function undo(): void
    {
        $this->travel('undo', 'redo');
    }

    public function redo(): void
    {
        $this->travel('redo', 'undo');
    }

    /* ---------------- Save ---------------- */

    public function saveDraft(): void
    {
        $this->record->update(['draft_blocks' => $this->blocks, 'draft_saved_at' => now()]);
        $this->hasChanges = false;

        Notification::make()->success()->title('Borrador guardado')
            ->body('Los cambios todavía no se ven en la tienda. Pulsa «Publicar» cuando quieras.')->send();
    }

    public function publish(): void
    {
        $changes = ['blocks' => $this->blocks, 'draft_blocks' => null, 'draft_saved_at' => null];

        if ($this->record->isHome()) {
            $changes['is_published'] = true;
        }

        $this->record->update($changes);
        PageEditorDraft::forget($this->record, auth()->user());
        $this->hasChanges = false;

        Notification::make()->success()->title('¡Página publicada!')->body('Los cambios ya se ven en la tienda.')->send();
    }

    public function discard(): void
    {
        PageEditorDraft::forget($this->record, auth()->user());
        $this->record->update(['draft_blocks' => null, 'draft_saved_at' => null]);
        $this->blocks = PageEditorDraft::saved($this->record->refresh());
        $this->forgetHistory();
        $this->hasChanges = false;
        $this->deselect();
        $this->panelVersion++;
        $this->dispatch('pb-refresh');

        Notification::make()->success()->title('Cambios descartados')->body('Vuelves a ver la versión publicada.')->send();
    }

    public function shopUrl(): string
    {
        return $this->record->isHome() ? route('store.home') : route('store.page', $this->record->slug);
    }

    /* ---------------- Internals ---------------- */

    /** Applies a change: history snapshot, working copy in the cache, preview refresh. */
    private function change(callable $mutate, bool $refresh = true): void
    {
        $history = $this->history();
        $history['undo'][] = $this->blocks;
        $history['undo'] = array_slice($history['undo'], -self::HISTORY_LIMIT);
        $history['redo'] = [];

        $this->blocks = array_values($mutate($this->blocks));
        $this->persist($history);

        if ($refresh) {
            $this->dispatch('pb-refresh');
        }
    }

    private function travel(string $from, string $to): void
    {
        $history = $this->history();

        if ($history[$from] === []) {
            return;
        }

        $history[$to][] = $this->blocks;
        $this->blocks = array_pop($history[$from]);
        $this->persist($history);

        if ($this->selected !== null && ! isset($this->blocks[$this->selected])) {
            $this->selected = null;
        }

        $this->panelVersion++;
        $this->dispatch('pb-refresh');
    }

    /** @param  array{undo: list<array>, redo: list<array>}  $history */
    private function persist(array $history): void
    {
        PageEditorDraft::put($this->record, auth()->user(), $this->blocks);
        Cache::put($this->historyKey(), $history, now()->addHours(12));
        $this->hasChanges = true;
        $this->syncHistoryFlags($history);
    }

    /** @return array{undo: list<array>, redo: list<array>} */
    private function history(): array
    {
        return Cache::get($this->historyKey(), ['undo' => [], 'redo' => []]);
    }

    private function forgetHistory(): void
    {
        Cache::forget($this->historyKey());
        $this->syncHistoryFlags();
    }

    private function syncHistoryFlags(?array $history = null): void
    {
        $history ??= $this->history();
        $this->canUndo = $history['undo'] !== [];
        $this->canRedo = $history['redo'] !== [];
    }

    private function historyKey(): string
    {
        return "page-editor-history:{$this->record->id}:".auth()->id();
    }
}
