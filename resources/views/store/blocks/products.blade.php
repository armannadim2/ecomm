{{-- Block "Productos" (Stitch: Feed Section). Source: newest, one category or on sale. --}}
@php
    $products = $block->payload['products'] ?? collect();
    $total = $block->payload['total'] ?? $products->count();
    $filterCategories = $block->payload['categories'] ?? collect();
    $initial = max(1, (int) $block->get('initial', 8));
    $anchor = \Illuminate\Support\Str::slug((string) $block->get('anchor', '')) ?: $block->key;
    $count = fn (int $shown) => $block->text('count_text', ['[mostrados]' => $shown, '[total]' => number_format($total, 0, ',', '.')]);
@endphp
<section id="{{ $anchor }}" class="mx-auto w-full max-w-[1280px] scroll-mt-48 px-4 py-8 lg:px-12" aria-labelledby="{{ $block->key }}-title" x-data="{ more: false }">
    <div class="mb-6 flex flex-col justify-between gap-4 border-b border-outline-variant/60 pb-4 sm:flex-row sm:items-center">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex size-7 items-center justify-center rounded-full bg-secondary-container text-secondary" aria-hidden="true">@svg('heroicon-o-building-storefront', 'size-[18px]')</span>
                <h2 id="{{ $block->key }}-title" class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $block->editable('title') }}</h2>
                @if ($block->has('badge'))<span class="rounded-full bg-secondary-container px-2.5 py-0.5 text-xs font-bold text-secondary">{{ $block->editable('badge') }}</span>@endif
            </div>
            @if ($block->has('text'))<p class="mt-1 text-xs font-medium text-outline sm:text-sm">{{ $block->editable('text') }}</p>@endif
        </div>
        @if ($filterCategories->isNotEmpty())
            <ul class="no-scrollbar flex items-center gap-2 overflow-x-auto py-1">
                @if ($block->has('all_label'))
                    <li><span class="block shrink-0 whitespace-nowrap rounded-xl bg-primary px-3.5 py-1.5 text-xs font-bold text-white shadow-sm" aria-current="true">{{ $block->editable('all_label') }}</span></li>
                @endif
                @foreach ($filterCategories as $category)
                    <li><a href="{{ route('store.category', $category->slug) }}" class="block shrink-0 whitespace-nowrap rounded-xl border border-outline-variant/60 bg-surface-container-low px-3.5 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-surface-container">{{ $category->name }}</a></li>
                @endforeach
                <li>
                    <a href="{{ route('store.search') }}" class="block shrink-0 rounded-xl border border-outline-variant/60 bg-surface-container-low p-1.5 text-primary transition-colors hover:bg-surface-container" title="{{ __('Buscar y filtrar') }}">
                        @svg('heroicon-o-adjustments-horizontal', 'size-5')<span class="sr-only">{{ __('Buscar y filtrar') }}</span>
                    </a>
                </li>
            </ul>
        @endif
    </div>

    @forelse ($products as $product)
        @if ($loop->first)<div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">@endif
        @if ($loop->index < $initial)
            @include('store.partials.product-card', ['product' => $product])
        @else
            <div class="contents" x-show="more" x-cloak>@include('store.partials.product-card', ['product' => $product])</div>
        @endif
        @if ($loop->last)</div>@endif
    @empty
        <p class="rounded-2xl border border-outline-variant/60 bg-white p-8 text-center text-base text-on-surface-variant">{{ __('Muy pronto tendremos productos disponibles.') }}</p>
    @endforelse

    @if ($products->isNotEmpty())
        <div class="mt-10 flex flex-col items-center justify-center gap-3">
            @if ($products->count() > $initial && $block->has('load_more_label'))
                <button type="button" x-show="!more" x-on:click="more = true" class="flex items-center gap-2 rounded-2xl border border-outline-variant/70 bg-white px-8 py-3 text-sm font-bold text-primary shadow-sm transition-all hover:bg-surface-container-low hover:shadow">
                    @svg('heroicon-o-arrow-path', 'size-5 text-secondary') <span>{{ $block->editable('load_more_label') }}</span>
                </button>
            @endif
            @if ($block->has('count_text'))
                <p class="text-xs font-medium text-outline" aria-live="polite">
                    <span x-show="!more">{{ $count(min($initial, $products->count())) }}</span>
                    <span x-show="more" x-cloak>{{ $count($products->count()) }}</span>
                </p>
            @endif
        </div>
    @endif
</section>
