{{--
    Filtered product listing shared by the category and search pages (Stitch "Cerca i Catàleg").
    Expects: $products (paginator), $filters (CatalogFilters), $brands, $sections (facets: slug, name, count),
    $inStockCount, $priceBounds ([lo, hi] cents | null), $sorts, $currentSort,
    $sectionMode ('link' = subcategory pages, 'filter' = ?categoria=), $heading (h1 text), $subheading (?string),
    $emptyText, and optionally $intro (?string, under the heading).
--}}
@php
    use App\Support\Money;

    $pickup = app(\App\Domain\Orders\Settings\CheckoutSettings::class);
    $hours = (new \App\Domain\Content\Blocks\Block('hours', ['h' => $pickup->opening_hours]))->text('h');
    $waUrl = \App\Domain\Content\Blocks\Block::resolveUrl('[whatsapp]');
    $reset = ['page' => null];
    $chips = [];
    if ($filters->inStock) {
        $chips[] = [__('En stock'), request()->fullUrlWithQuery(['stock' => null] + $reset)];
    }
    if ($filters->minCents !== null || $filters->maxCents !== null) {
        $chips[] = [
            match (true) {
                $filters->minCents !== null && $filters->maxCents !== null => __('Precio: :min – :max', ['min' => Money::format($filters->minCents), 'max' => Money::format($filters->maxCents)]),
                $filters->minCents !== null => __('Desde :min', ['min' => Money::format($filters->minCents)]),
                default => __('Hasta :max', ['max' => Money::format($filters->maxCents)]),
            },
            request()->fullUrlWithQuery(['min' => null, 'max' => null] + $reset),
        ];
    }
    foreach ($filters->brands as $slug) {
        $chips[] = [
            $brands->firstWhere('slug', $slug)['name'] ?? $slug,
            request()->fullUrlWithQuery(['marca' => array_values(array_diff($filters->brands, [$slug]))] + $reset),
        ];
    }
    if ($filters->category) {
        $chips[] = [$sections->firstWhere('slug', $filters->category)['name'] ?? $filters->category, request()->fullUrlWithQuery(['categoria' => null] + $reset)];
    }
    $clearUrl = request()->url().(request()->filled('q') ? '?'.http_build_query(['q' => request('q')]) : '');
    $panel = 'rounded-2xl bg-white p-4 shadow-sm';
    $panelTitle = 'mb-2 flex items-center gap-2 text-base font-bold text-primary';
@endphp

<section class="rounded-2xl bg-white p-4 shadow-sm sm:p-5">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <div class="flex flex-wrap items-baseline gap-x-2">
                <h1 class="text-2xl font-extrabold tracking-tight text-primary sm:text-3xl">{{ $heading }}</h1>
                @if ($subheading)
                    <span class="text-base font-medium text-on-surface-variant">{{ $subheading }}</span>
                @endif
            </div>
            @if (! empty($intro))
                <p class="mt-1 max-w-2xl text-sm text-on-surface-variant">{{ $intro }}</p>
            @endif
        </div>
        <form method="get" class="flex shrink-0 items-center gap-2 self-start lg:self-center">
            @foreach (request()->except(['orden', 'page']) as $key => $value)
                @foreach ((array) $value as $v)
                    <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $v }}">
                @endforeach
            @endforeach
            <label for="orden" class="flex items-center gap-1 whitespace-nowrap text-sm font-semibold text-on-surface-variant">@svg('heroicon-o-arrows-up-down', 'size-4') {{ __('Ordenar por') }}</label>
            <select id="orden" name="orden" onchange="this.form.submit()" class="rounded-full border-0 bg-surface-container-low py-2 pl-3 pr-8 text-sm font-bold text-primary focus:ring-2 focus:ring-secondary">
                @foreach ($sorts as $key => $label)
                    <option value="{{ $key }}" @selected($key === $currentSort)>{{ __($label) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="rounded-full bg-primary px-3 py-2 text-xs font-bold text-white">{{ __('Aplicar') }}</button></noscript>
        </form>
    </div>

    @if ($chips)
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="mr-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Filtros activos:') }}</span>
            @foreach ($chips as [$label, $url])
                <a href="{{ $url }}" class="inline-flex items-center gap-1.5 rounded-full bg-primary py-1 pl-3 pr-2 text-xs font-semibold text-white hover:bg-primary-container">
                    {{ $label }}
                    <span class="flex size-4 items-center justify-center rounded-full bg-primary-container" aria-hidden="true">@svg('heroicon-o-x-mark', 'size-3')</span>
                    <span class="sr-only">{{ __('(quitar filtro)') }}</span>
                </a>
            @endforeach
            <a href="{{ $clearUrl }}" class="inline-flex items-center gap-1 text-xs font-bold text-secondary hover:underline">@svg('heroicon-o-arrow-path', 'size-4') {{ __('Quitar todos los filtros') }}</a>
        </div>
    @endif
</section>

<div class="mt-5 grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
    {{-- Filters: a GET form, applied on change (button kept for keyboard and no-JS users). --}}
    <aside class="lg:col-span-3" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between rounded-2xl bg-white px-4 py-3 text-sm font-bold text-primary shadow-sm lg:hidden"
                x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="listing-filters">
            <span class="flex items-center gap-2">@svg('heroicon-o-adjustments-horizontal', 'size-5 text-secondary') {{ __('Filtros') }}@if ($chips) <span class="rounded-full bg-secondary px-2 text-[11px] text-white">{{ count($chips) }}</span>@endif</span>
            @svg('heroicon-o-chevron-down', 'size-4')
        </button>

        <form method="get" id="listing-filters" class="mt-3 hidden flex-col gap-4 lg:mt-0 lg:flex" x-bind:class="open ? '!flex' : ''">
            @if (request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            @if (request()->filled('orden'))<input type="hidden" name="orden" value="{{ request('orden') }}">@endif
            @if ($filters->category && $sectionMode === 'filter')<input type="hidden" name="categoria" value="{{ $filters->category }}">@endif

            <div class="{{ $panel }}">
                <h2 class="{{ $panelTitle }}">@svg('heroicon-o-bolt', 'size-5 text-accent') {{ __('Disponibilidad') }}</h2>
                <label class="flex cursor-pointer items-center justify-between rounded-lg p-2 hover:bg-surface-container-low">
                    <span class="flex items-center gap-2.5">
                        <input type="checkbox" name="stock" value="1" @checked($filters->inStock) onchange="this.form.submit()" class="size-4 rounded accent-secondary">
                        <span class="text-sm font-medium text-primary">{{ __('En stock en la tienda') }}</span>
                    </span>
                    <span class="rounded-full bg-surface-container px-2 py-0.5 text-[11px] font-bold">{{ $inStockCount }}</span>
                </label>
                @if ($pickup->pickup_enabled)
                    <p class="mt-1 px-2 text-xs text-on-surface-variant">{{ __('Recogida gratis en la tienda en cuanto esté lista tu compra.') }}</p>
                @endif
            </div>

            @if ($sections->isNotEmpty())
                <div class="{{ $panel }}">
                    <h2 class="{{ $panelTitle }}">@svg('heroicon-o-squares-2x2', 'size-5 text-accent') {{ $sectionMode === 'link' ? __('Subcategorías') : __('Sección') }}</h2>
                    <ul class="flex flex-col gap-1">
                        @foreach ($sections as $section)
                            @php
                                $active = $sectionMode === 'filter' && $filters->category === $section['slug'];
                                $href = $sectionMode === 'link'
                                    ? route('store.category', $section['slug'])
                                    : request()->fullUrlWithQuery(['categoria' => $active ? null : $section['slug']] + $reset);
                            @endphp
                            <li>
                                <a href="{{ $href }}" @if ($active) aria-current="true" @endif
                                   @class(['flex items-center justify-between rounded-lg px-2 py-1.5 text-sm transition-colors', 'bg-primary font-bold text-white' => $active, 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary' => ! $active])>
                                    <span>{{ $section['name'] }}</span>
                                    <span @class(['text-[11px] font-bold', 'text-white' => $active, 'text-outline' => ! $active])>{{ $section['count'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($priceBounds)
                <div class="{{ $panel }}">
                    <h2 class="{{ $panelTitle }}">@svg('heroicon-o-currency-euro', 'size-5 text-accent') {{ __('Precio (€)') }}</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex flex-col gap-1 rounded-lg bg-surface-container-low px-3 py-1.5">
                            <span class="text-[11px] font-bold uppercase text-on-surface-variant">{{ __('Mín.') }}</span>
                            <input type="number" name="min" min="0" step="1" inputmode="decimal"
                                   value="{{ $filters->minCents !== null ? $filters->minCents / 100 : '' }}"
                                   placeholder="{{ floor($priceBounds[0] / 100) }}"
                                   class="w-full border-0 bg-transparent p-0 text-sm font-bold text-primary focus:ring-0">
                        </label>
                        <label class="flex flex-col gap-1 rounded-lg bg-surface-container-low px-3 py-1.5">
                            <span class="text-[11px] font-bold uppercase text-on-surface-variant">{{ __('Máx.') }}</span>
                            <input type="number" name="max" min="0" step="1" inputmode="decimal"
                                   value="{{ $filters->maxCents !== null ? $filters->maxCents / 100 : '' }}"
                                   placeholder="{{ ceil($priceBounds[1] / 100) }}"
                                   class="w-full border-0 bg-transparent p-0 text-sm font-bold text-primary focus:ring-0">
                        </label>
                    </div>
                </div>
            @endif

            @if ($brands->isNotEmpty())
                <div class="{{ $panel }}">
                    <h2 class="{{ $panelTitle }}">@svg('heroicon-o-tag', 'size-5 text-accent') {{ __('Marcas') }}</h2>
                    <div class="flex flex-col gap-1">
                        @foreach ($brands as $brand)
                            <label class="flex cursor-pointer items-center justify-between rounded-lg p-1.5 hover:bg-surface-container-low">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" name="marca[]" value="{{ $brand['slug'] }}" @checked(in_array($brand['slug'], $filters->brands, true)) onchange="this.form.submit()" class="size-4 rounded accent-secondary">
                                    <span class="text-sm text-primary">{{ $brand['name'] }}</span>
                                </span>
                                <span class="text-xs text-outline">{{ $brand['count'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <button type="submit" class="rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-primary-container">{{ __('Aplicar filtros') }}</button>
        </form>
    </aside>

    <div class="flex flex-col gap-6 lg:col-span-9">
        @if ($pickup->pickup_enabled && $store->address_line)
            <div class="flex flex-col justify-between gap-3 rounded-2xl bg-primary p-4 text-white shadow-sm sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary" aria-hidden="true">@svg('heroicon-o-building-storefront', 'size-5')</span>
                    <div>
                        <p class="text-sm font-bold">{{ __('Compra online y recoge en la tienda') }}</p>
                        <p class="text-xs text-white/80">{{ $store->address_line }}@if ($hours) · {{ $hours }}@endif</p>
                    </div>
                </div>
                <span class="self-start rounded-full bg-primary-container px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider sm:self-center">{{ __('Recogida gratis') }}</span>
            </div>
        @endif

        @if ($products->isEmpty())
            <div class="rounded-2xl bg-white p-8 text-center text-on-surface-variant shadow-sm">
                <p>{{ $emptyText }}</p>
                @if ($chips)
                    <a href="{{ $clearUrl }}" class="mt-3 inline-block font-bold text-secondary hover:underline">{{ __('Quitar todos los filtros') }}</a>
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:gap-6">
                @foreach ($products as $product)
                    @include('store.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            {{ $products->links('store.partials.pagination') }}
        @endif

        @if ($waUrl)
            <div class="flex flex-col items-start justify-between gap-4 rounded-2xl bg-surface-container-low p-4 shadow-sm sm:flex-row sm:items-center">
                <div class="flex items-center gap-4">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-tertiary-fixed text-primary" aria-hidden="true">@svg('heroicon-o-chat-bubble-left-right', 'size-6')</span>
                    <div>
                        <h2 class="text-base font-bold text-primary">{{ __('¿No encuentras lo que buscas?') }}</h2>
                        <p class="text-sm text-on-surface-variant">{{ __('Escríbenos: te decimos si lo tenemos en la tienda o podemos conseguirlo.') }}</p>
                    </div>
                </div>
                <a href="{{ $waUrl }}?text={{ rawurlencode(__('¡Hola! Busco un producto:')) }}" target="_blank" rel="noopener"
                   class="inline-flex shrink-0 items-center gap-2 rounded-full bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-container">
                    @svg('heroicon-o-chat-bubble-oval-left', 'size-5') {{ __('WhatsApp: :phone', ['phone' => $store->phone]) }}
                    <span class="sr-only">{{ __('(se abre en una pestaña nueva)') }}</span>
                </a>
            </div>
        @endif
    </div>
</div>
