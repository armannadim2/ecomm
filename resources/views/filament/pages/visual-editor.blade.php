{{--
    Visual page editor (App\Filament\Resources\Pages\Pages\VisualEditor). Full-screen over the admin:
    top bar · left panel (Añadir / Estructura / Editar) · live preview iframe of the real page.
    The iframe runs resources/js/page-editor-preview.js and talks to this page with postMessage.
--}}
@php
    use App\Domain\Content\Blocks\BlockTypes;
    $listTitle = function (array $block): string {
        $data = $block['data'] ?? [];
        $raw = $data['title'] ?? $data['label'] ?? $data['eyebrow'] ?? null;
        $text = is_array($raw) ? ($raw['es'] ?? '') : (string) $raw;

        return \Illuminate\Support\Str::limit(strip_tags($text), 42);
    };
@endphp

<x-filament-panels::page>
    @vite('resources/js/page-editor.js')

    {{-- The editor covers the admin at z-index 100: notifications and modals must stay above it. --}}
    <style>
        .fi-no, .fi-modal { z-index: 120 !important; }
        .fi-no { padding-top: 4.5rem !important; } /* below the top bar, not over «Publicar» */
    </style>

    {{-- Teleported to <body> so the editor covers the whole admin (sidebar and top bar included). --}}
    @teleport('body')
    <div class="fixed inset-0 z-[100] flex flex-col bg-gray-100 dark:bg-gray-950"
         {{-- Keep this x-data free of values that change between requests: if the attribute
              changes, Alpine rebuilds the editor and loses the tab, device and preview. --}}
         x-data="{
            tab: 'add',
            device: 'desktop',
            scrollY: 0,
            loading: true,
            frame() { return this.$refs.preview },
            post(action, payload = {}) { this.frame()?.contentWindow?.postMessage({ source: 'pb-editor', action, ...payload }, window.location.origin) },
            reload() {
                this.loading = true;
                const url = new URL(@js(route('admin.page-editor.preview', ['page' => $this->record])));
                url.searchParams.set('locale', $wire.locale);
                url.searchParams.set('v', Date.now());
                this.frame().src = url.toString();
            },
            onFrameLoad() {
                this.loading = false;
                try { this.frame().contentWindow.scrollTo(0, this.scrollY); } catch (e) {}
                if ($wire.selected !== null) this.post('selected', { index: $wire.selected });
            },
            onMessage(event) {
                if (event.origin !== window.location.origin || event.data?.source !== 'pb-preview') return;
                const d = event.data;
                switch (d.action) {
                    case 'scroll': this.scrollY = d.y; break;
                    case 'select': $wire.select(d.index, d.keepPanel ?? false); this.tab = 'edit'; break;
                    case 'move': $wire.move(d.index, d.to); break;
                    case 'reorder': $wire.reorder(d.order); break;
                    case 'duplicate': $wire.duplicate(d.index); break;
                    case 'toggle': $wire.toggle(d.index); break;
                    case 'delete': if (confirm('¿Eliminar esta sección de la página?')) $wire.remove(d.index); break;
                    case 'insert': $wire.insert(d.type, d.at); this.tab = 'edit'; break;
                    case 'text': $wire.updateText(d.path, d.value); break;
                }
            },
            // Runs on the list itself: Livewire swaps the <ul> after every change, so each new one is set up again.
            initStructure(list) {
                if (!window.PbSortable) return setTimeout(() => this.initStructure(list), 100);
                if (list._pbSortable) return;
                list._pbSortable = window.PbSortable.create(list, {
                    handle: '[data-handle]', animation: 160, forceFallback: true,
                    onEnd: (e) => {
                        if (e.oldIndex === e.newIndex) return;
                        $wire.reorder([...list.children].map((li) => Number(li.dataset.index)));
                    },
                });
            },
         }"
         x-init="reload()"
         x-on:message.window="onMessage($event)"
         x-on:pb-refresh.window="reload()"
         x-on:pb-selected.window="post('selected', { index: $event.detail.index }); if ($event.detail.index !== null) { tab = 'edit'; post('scrollTo', { index: $event.detail.index }) }"
         x-on:beforeunload.window="if ($wire.hasChanges) { $event.preventDefault(); $event.returnValue = '' }"
         x-on:keydown.ctrl.z.window.prevent="$wire.undo()"
         x-on:keydown.ctrl.y.window.prevent="$wire.redo()">

        {{-- Top bar --}}
        <header class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 dark:border-white/10 dark:bg-gray-900">
            <a href="{{ \App\Filament\Resources\Pages\PageResource::getUrl() }}"
               x-on:click="if ($wire.hasChanges && ! confirm('Tienes cambios sin guardar. ¿Salir igualmente?')) $event.preventDefault()"
               class="flex size-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-white/5" title="Volver a Páginas">
                <x-filament::icon icon="heroicon-o-arrow-left" class="size-5" />
            </a>
            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm font-bold text-gray-950 dark:text-white">{{ $this->record->title }}</span>
                <span class="text-xs" :class="$wire.hasChanges ? 'text-warning-600' : 'text-success-600'"
                      x-text="$wire.hasChanges ? 'Cambios sin publicar' : 'Todo guardado'"></span>
            </div>

            <div class="mx-auto flex items-center gap-3">
                {{-- Language --}}
                <div class="flex rounded-lg bg-gray-100 p-0.5 text-xs font-bold dark:bg-white/5" role="group" aria-label="Idioma de la vista previa">
                    @foreach (config('app.available_locales') as $code => $name)
                        <button type="button" wire:click="setLocale('{{ $code }}')" title="{{ $name }}"
                                @class(['rounded-md px-2.5 py-1.5 uppercase', 'bg-white text-primary-700 shadow-sm dark:bg-gray-800' => $locale === $code, 'text-gray-500 hover:text-gray-900' => $locale !== $code])>{{ $code }}</button>
                    @endforeach
                </div>
                {{-- Device --}}
                <div class="hidden rounded-lg bg-gray-100 p-0.5 sm:flex dark:bg-white/5" role="group" aria-label="Tamaño de pantalla">
                    @foreach (['desktop' => ['heroicon-o-computer-desktop', 'Ordenador'], 'tablet' => ['heroicon-o-device-tablet', 'Tableta'], 'mobile' => ['heroicon-o-device-phone-mobile', 'Móvil']] as $device => [$icon, $label])
                        <button type="button" x-on:click="device = '{{ $device }}'" title="{{ $label }}"
                                class="rounded-md px-2.5 py-1.5" :class="device === '{{ $device }}' ? 'bg-white text-primary-700 shadow-sm dark:bg-gray-800' : 'text-gray-500 hover:text-gray-900'">
                            <x-filament::icon :icon="$icon" class="size-4" /><span class="sr-only">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
                {{-- Undo / redo --}}
                <div class="flex">
                    <button type="button" wire:click="undo" @disabled(! $canUndo) class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 disabled:opacity-30 dark:text-gray-300 dark:hover:bg-white/5" title="Deshacer (Ctrl+Z)">
                        <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="size-5" /><span class="sr-only">Deshacer</span>
                    </button>
                    <button type="button" wire:click="redo" @disabled(! $canRedo) class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 disabled:opacity-30 dark:text-gray-300 dark:hover:bg-white/5" title="Rehacer (Ctrl+Y)">
                        <x-filament::icon icon="heroicon-o-arrow-uturn-right" class="size-5" /><span class="sr-only">Rehacer</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button color="gray" size="sm" tag="a" :href="$this->shopUrl()" target="_blank" icon="heroicon-o-arrow-top-right-on-square" class="hidden lg:inline-flex">
                    Ver en la tienda
                </x-filament::button>
                @if ($this->record->draft_blocks || $hasChanges)
                    <x-filament::button color="gray" size="sm" wire:click="discard" wire:confirm="¿Descartar todos los cambios sin publicar? Volverás a la versión que se ve en la tienda." class="hidden md:inline-flex">
                        Descartar
                    </x-filament::button>
                @endif
                <x-filament::button color="gray" size="sm" wire:click="saveDraft" icon="heroicon-o-bookmark">
                    Guardar borrador
                </x-filament::button>
                <x-filament::button size="sm" wire:click="publish" icon="heroicon-o-cloud-arrow-up">
                    Publicar
                </x-filament::button>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            {{-- Left panel --}}
            <aside class="flex w-[360px] shrink-0 flex-col border-r border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
                <nav class="grid grid-cols-3 border-b border-gray-200 text-sm font-semibold dark:border-white/10" aria-label="Panel del editor">
                    @foreach (['add' => ['heroicon-o-plus-circle', 'Añadir'], 'structure' => ['heroicon-o-bars-3', 'Estructura'], 'edit' => ['heroicon-o-adjustments-horizontal', 'Editar']] as $key => [$icon, $label])
                        <button type="button" x-on:click="tab = '{{ $key }}'"
                                class="flex items-center justify-center gap-1.5 border-b-2 py-3"
                                :class="tab === '{{ $key }}' ? 'border-primary-600 text-primary-700 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-white'">
                            <x-filament::icon :icon="$icon" class="size-4" /> {{ $label }}
                        </button>
                    @endforeach
                </nav>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    {{-- Library --}}
                    <div x-show="tab === 'add'">
                        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Arrastra una sección a la página o haz clic para añadirla {{ $selected !== null ? 'debajo de la seleccionada' : 'al final' }}.</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (BlockTypes::META as $type => [$label, $description, $icon])
                                <button type="button" draggable="true"
                                        x-on:dragstart="$event.dataTransfer.setData('application/x-pb-block', '{{ $type }}'); $event.dataTransfer.effectAllowed = 'copy'"
                                        wire:click="insert('{{ $type }}')"
                                        class="group flex cursor-grab flex-col items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 p-3 text-center transition hover:border-primary-400 hover:bg-primary-50 active:cursor-grabbing dark:border-white/10 dark:bg-white/5 dark:hover:bg-primary-500/10"
                                        title="{{ $description }}">
                                    <span class="flex size-10 items-center justify-center rounded-lg bg-white text-primary-600 shadow-sm group-hover:bg-primary-600 group-hover:text-white dark:bg-gray-800">
                                        <x-filament::icon :icon="'heroicon-o-'.$icon" class="size-5" />
                                    </span>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $label }}</span>
                                    <span class="text-[11px] leading-tight text-gray-500 dark:text-gray-400">{{ $description }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Structure --}}
                    <div x-show="tab === 'structure'" x-cloak>
                        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">Arrastra para cambiar el orden. Haz clic para editar.</p>
                        @if ($blocks === [])
                            <p class="rounded-xl border-2 border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-white/10">La página todavía no tiene secciones.</p>
                        @endif
                        <ul x-ref="structure" x-init="initStructure($el)" class="flex flex-col gap-1.5" wire:key="structure-{{ md5(json_encode(array_column($blocks, 'type'))) }}-{{ count($blocks) }}">
                            @foreach ($blocks as $i => $block)
                                <li data-index="{{ $i }}" wire:key="structure-item-{{ $i }}-{{ $block['type'] }}"
                                    @class(['flex items-center gap-2 rounded-lg border px-2 py-2 text-sm', 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' => $selected === $i, 'border-gray-200 bg-white hover:border-gray-300 dark:border-white/10 dark:bg-gray-900' => $selected !== $i])>
                                    <span data-handle class="cursor-grab text-gray-400 hover:text-gray-700" title="Arrastra para mover"><x-filament::icon icon="heroicon-o-bars-3" class="size-4" /></span>
                                    <button type="button" wire:click="select({{ $i }})" class="flex min-w-0 flex-1 items-center gap-2 text-left">
                                        <x-filament::icon :icon="'heroicon-o-'.(BlockTypes::META[$block['type']][2] ?? 'square-3-stack-3d')" class="size-4 shrink-0 text-primary-600" />
                                        <span class="min-w-0">
                                            <span class="block truncate font-semibold text-gray-900 dark:text-white">{{ BlockTypes::label($block['type']) }}</span>
                                            @if ($title = $listTitle($block))<span class="block truncate text-xs text-gray-500">{{ $title }}</span>@endif
                                        </span>
                                    </button>
                                    @if (! empty($block['data']['is_hidden']))
                                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:bg-white/10">Oculto</span>
                                    @endif
                                    <button type="button" wire:click="toggle({{ $i }})" class="text-gray-400 hover:text-gray-700" title="Mostrar u ocultar">
                                        <x-filament::icon :icon="empty($block['data']['is_hidden']) ? 'heroicon-o-eye' : 'heroicon-o-eye-slash'" class="size-4" />
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Selected block settings --}}
                    <div x-show="tab === 'edit'" x-cloak>
                        @if ($selected !== null && isset($blocks[$selected]))
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon :icon="'heroicon-o-'.(BlockTypes::META[$blocks[$selected]['type']][2] ?? 'square-3-stack-3d')" class="size-5 text-primary-600" />
                                    <span class="text-sm font-bold text-gray-950 dark:text-white">{{ BlockTypes::label($blocks[$selected]['type']) }}</span>
                                </div>
                                <div class="flex gap-1">
                                    <button type="button" wire:click="duplicate({{ $selected }})" class="rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5" title="Duplicar"><x-filament::icon icon="heroicon-o-document-duplicate" class="size-4" /></button>
                                    <button type="button" wire:click="remove({{ $selected }})" wire:confirm="¿Eliminar esta sección de la página?" class="rounded p-1.5 text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-500/10" title="Eliminar"><x-filament::icon icon="heroicon-o-trash" class="size-4" /></button>
                                    <button type="button" wire:click="deselect" class="rounded p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5" title="Cerrar"><x-filament::icon icon="heroicon-o-x-mark" class="size-4" /></button>
                                </div>
                            </div>
                            <p class="mb-3 rounded-lg bg-primary-50 px-3 py-2 text-xs text-primary-800 dark:bg-primary-500/10 dark:text-primary-300">
                                Consejo: también puedes hacer clic en cualquier texto de la página y escribir directamente.
                            </p>
                            <livewire:page-block-panel
                                :type="$blocks[$selected]['type']"
                                :block-data="$blocks[$selected]['data']"
                                :key="'panel-'.$selected.'-'.$panelVersion" />
                        @else
                            <div class="flex flex-col items-center gap-2 rounded-xl border-2 border-dashed border-gray-200 p-8 text-center dark:border-white/10">
                                <x-filament::icon icon="heroicon-o-cursor-arrow-rays" class="size-8 text-gray-400" />
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Haz clic en una sección de la página para editarla</p>
                                <p class="text-xs text-gray-500">O añade una nueva desde «Añadir».</p>
                            </div>
                        @endif
                    </div>
                </div>
            </aside>

            {{-- Canvas --}}
            {{-- wire:ignore: Livewire re-renders must not reset the iframe (its address is set by Alpine). --}}
            <main wire:ignore class="relative flex min-w-0 flex-1 justify-center overflow-auto bg-gray-200/70 p-4 dark:bg-black/40">
                <div class="relative h-full overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-black/5 transition-all duration-300"
                     :style="{ width: device === 'desktop' ? '100%' : (device === 'tablet' ? '820px' : '390px') }">
                    <iframe x-ref="preview" x-on:load="onFrameLoad()" class="size-full" title="Vista previa de la página"></iframe>
                    <div x-show="loading" x-transition.opacity class="pointer-events-none absolute right-3 top-3 flex items-center gap-2 rounded-full bg-gray-900/80 px-3 py-1.5 text-xs font-semibold text-white">
                        <x-filament::loading-indicator class="size-4" /> Actualizando…
                    </div>
                </div>
            </main>
        </div>
    </div>
    @endteleport
</x-filament-panels::page>
