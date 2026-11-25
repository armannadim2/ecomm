{{-- Block "Portada" (Stitch: Editorial Hero Section). Mosaic = the 4 newest products. --}}
@php
    $products = $block->payload['products'] ?? collect();
    $mosaic = $products->take(4)->values()->map(fn ($p) => [
        'name' => $p->name, 'url' => $p->imageUrl('medium'), 'slug' => $p->slug,
        'price' => \App\Support\Money::format($p->min_price_cents ?? $p->variants->min('price_cents')),
    ]);
    $primary = $block->url('primary_url');
    $secondary = $block->url('secondary_url');
    $stats = array_slice($block->items('stats'), 0, 3);
    $statColours = ['text-primary', 'text-secondary', 'text-primary'];
@endphp
<section class="mx-auto w-full max-w-[1280px] px-4 py-4 lg:px-12">
    <div class="relative overflow-hidden rounded-3xl border border-outline-variant/70 bg-surface-container-low shadow-sm">
        <div class="pointer-events-none absolute -right-20 -top-20 size-96 rounded-full bg-secondary-container opacity-80 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-12 size-80 rounded-full bg-surface/50 blur-3xl" aria-hidden="true"></div>

        <div class="relative z-10 grid grid-cols-1 items-center gap-8 p-6 sm:p-10 lg:grid-cols-12 lg:p-14">
            <div @class(['flex flex-col items-start gap-4', 'lg:col-span-7' => $mosaic->isNotEmpty(), 'lg:col-span-12' => $mosaic->isEmpty()])>
                @if ($block->has('eyebrow'))
                    <span class="inline-flex items-center gap-2 rounded-full border border-secondary/20 bg-white px-3.5 py-1.5 font-bold text-secondary shadow-sm">
                        @svg('heroicon-o-building-storefront', 'size-[18px]')
                        <span class="text-xs uppercase tracking-wider">{{ $block->editable('eyebrow') }}</span>
                    </span>
                @endif
                @if ($block->has('title'))
                    @if (($headingLevel ?? 1) === 1)
                        <h1 class="text-3xl font-extrabold leading-[1.15] tracking-tight text-primary sm:text-4xl lg:text-5xl">{{ $block->editable('title') }}</h1>
                    @else
                        <h2 class="text-3xl font-extrabold leading-[1.15] tracking-tight text-primary sm:text-4xl lg:text-5xl">{{ $block->editable('title') }}</h2>
                    @endif
                @endif
                @if ($block->has('text'))
                    <p class="max-w-xl text-base leading-relaxed text-on-surface-variant sm:text-lg">{{ $block->editable('text') }}</p>
                @endif
                @if (($primary && $block->has('primary_label')) || ($secondary && $block->has('secondary_label')))
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        @if ($primary && $block->has('primary_label'))
                            <a @include('store.blocks._link', ['url' => $primary]) class="flex items-center gap-2 rounded-2xl bg-secondary px-6 py-3.5 text-sm font-bold text-white shadow-cta transition-all hover:bg-secondary-hover hover:shadow-xl active:scale-[0.98]">
                                @svg('heroicon-o-shopping-bag', 'size-5') <span>{{ $block->editable('primary_label') }}</span>
                            </a>
                        @endif
                        @if ($secondary && $block->has('secondary_label'))
                            <a @include('store.blocks._link', ['url' => $secondary]) class="flex items-center gap-2 rounded-2xl border border-outline-variant bg-white px-6 py-3.5 text-sm font-bold text-primary shadow-sm transition-all hover:bg-surface-container-low hover:shadow-md">
                                @svg('heroicon-o-map', 'size-5 text-secondary') <span>{{ $block->editable('secondary_label') }}</span>
                            </a>
                        @endif
                    </div>
                @endif
                @if ($stats !== [])
                    <dl class="no-scrollbar mt-2 flex w-full gap-2 overflow-x-auto sm:grid sm:max-w-lg sm:grid-cols-3 sm:gap-6 sm:overflow-visible sm:border-t sm:border-outline-variant/60 sm:pt-6">
                        @foreach ($stats as $stat)
                            <div class="flex shrink-0 flex-row-reverse items-baseline justify-end gap-1.5 rounded-xl border border-outline-variant/60 bg-white px-3 py-2 shadow-sm sm:flex-col-reverse sm:items-start sm:gap-0 sm:rounded-none sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
                                <dt class="text-xs font-semibold text-on-surface sm:mt-0.5 sm:font-medium sm:text-outline">{{ $stat->editable('label') }}</dt>
                                <dd @class(['text-base font-extrabold sm:text-2xl', $statColours[$loop->index] ?? 'text-primary'])>{{ $stat->editable('value') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            @if ($mosaic->isNotEmpty())
                <div class="relative hidden sm:block lg:col-span-5">
                    <div class="grid grid-cols-2 gap-3.5">
                        @foreach ([[0, 1], [2, 3]] as $col => $slots)
                            <div @class(['flex flex-col gap-3.5', 'pt-6' => $col === 1])>
                                @foreach ($slots as $slot)
                                    @php $tile = $mosaic->get($slot); @endphp
                                    @if ($tile)
                                        @php $tall = $slot === 0 || $slot === 3; @endphp
                                        <a href="{{ route('store.product', $tile['slug']) }}" class="group relative block overflow-hidden rounded-2xl border border-outline-variant/60 bg-surface-container shadow-md">
                                            @if ($tile['url'])
                                                <img src="{{ $tile['url'] }}" alt="{{ $tile['name'] }}" @class(['w-full object-cover transition-transform duration-500 group-hover:scale-105', $tall ? 'h-52' : 'h-36'])>
                                            @else
                                                <span @class(['flex w-full items-center justify-center text-outline', $tall ? 'h-52' : 'h-36']) aria-hidden="true">@svg('heroicon-o-photo', 'size-10')</span>
                                            @endif
                                            @if ($slot === 1)
                                                <span class="absolute right-2 top-2 rounded-full bg-secondary px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-white">{{ __('Novedad') }}</span>
                                            @endif
                                            @if ($slot === 3 && $block->has('mosaic_badge'))
                                                <span class="absolute bottom-2 right-2 flex items-center gap-1 rounded-full bg-primary px-2.5 py-1 text-xs font-bold text-white shadow-md">🏪 {{ $block->editable('mosaic_badge') }}</span>
                                            @elseif ($slot !== 1)
                                                <span class="absolute bottom-2 left-2 max-w-[85%] rounded-lg border border-outline-variant/40 bg-white/95 px-2.5 py-1 backdrop-blur-md">
                                                    <span class="line-clamp-1 text-xs font-semibold text-primary">{{ $tile['name'] }}</span>
                                                    @if ($slot === 0)<span class="text-xs font-extrabold text-secondary">{{ __('Desde :price', ['price' => $tile['price']]) }}</span>@endif
                                                </span>
                                            @endif
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
