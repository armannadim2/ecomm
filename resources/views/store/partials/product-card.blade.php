{{--
    Product card (Stitch "Inicio - Tot & Més Barcelona", product grid).
    Expects: $product (Product with media, variants, brand, categories loaded and min_price_cents).
    The favourites heart of the design appears when favourites exist; the button says
    "Añadir a la cesta" once the cart route exists, "Ver producto" until then.
--}}
@php
    $variants = $product->variants;
    $minPrice = $product->min_price_cents ?? $variants->min('price_cents');
    $hasRange = $variants->pluck('price_cents')->unique()->count() > 1;
    $cheapest = $variants->firstWhere('price_cents', $minPrice);
    $compare = $cheapest?->compare_at_price_cents;
    $onSale = $compare && $compare > $minPrice;
    $available = $variants->contains(fn ($v) => ! $v->track_stock || $v->stock > 0);
    $image = $product->imageUrl('medium');
    $url = route('store.product', $product->slug);
    $meta = collect([
        $product->relationLoaded('categories') ? $product->categories->first()?->name : null,
        $product->brand?->name,
        $cheapest?->sku ? __('SKU: :sku', ['sku' => $cheapest->sku]) : null,
    ])->filter()->implode(' • ');
    $chip = $variants->count() > 1 ? trans_choice(':count opción|:count opciones', $variants->count()) : null;
@endphp
<article class="group relative flex flex-col rounded-2xl border border-outline-variant/60 bg-white p-3 shadow-sm transition-all duration-300 hover:shadow-lg">
    <div class="relative aspect-[3/4] w-full overflow-hidden rounded-xl bg-surface-container">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $product->name }}" loading="lazy"
                 class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
        @else
            <div class="flex size-full items-center justify-center text-outline" aria-hidden="true">@svg('heroicon-o-photo', 'size-10')</div>
        @endif

        @if ($available)
            <span class="absolute left-2.5 top-2.5 flex items-center gap-1 rounded-full border border-secondary/20 bg-white/95 px-2 py-0.5 text-[11px] font-bold text-secondary shadow-sm">
                <span class="size-1.5 rounded-full bg-accent" aria-hidden="true"></span>{{ __('En stock BCN') }}
            </span>
        @else
            <span class="absolute left-2.5 top-2.5 rounded-full bg-primary px-2 py-0.5 text-[11px] font-bold text-white shadow-sm">{{ __('Agotado') }}</span>
        @endif

        @if ($chip)
            <span class="absolute bottom-2.5 right-2.5 rounded-lg bg-white/95 px-2 py-1 text-xs font-semibold text-outline shadow-sm backdrop-blur-md">{{ $chip }}</span>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-1.5 p-2">
        <p class="flex items-baseline justify-between gap-2">
            <span class="text-xl font-extrabold tracking-tight text-primary">
                @if ($hasRange)<span class="mr-1 text-xs font-medium text-outline">{{ __('desde') }}</span>@endif{{ \App\Support\Money::format($minPrice) }}
            </span>
            @if ($onSale)
                <del class="text-xs text-outline"><span class="sr-only">{{ __('Antes') }} </span>{{ \App\Support\Money::format($compare) }}</del>
            @endif
        </p>
        <h3 class="truncate text-sm font-bold text-primary transition-colors group-hover:text-secondary">
            {{-- Stretched link: the whole card is clickable, one tab stop. --}}
            <a href="{{ $url }}" class="after:absolute after:inset-0 after:rounded-2xl focus-visible:outline-none">{{ $product->name }}</a>
        </h3>
        @if ($meta)
            <p class="line-clamp-2 text-xs text-outline">{{ $meta }}</p>
        @endif
        <div class="mt-auto border-t border-outline-variant/40 pt-2">
            <span class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-secondary py-2 text-xs font-bold text-white shadow-sm transition-colors group-hover:bg-secondary-hover group-has-[a:focus-visible]:outline-2 group-has-[a:focus-visible]:outline-offset-2 group-has-[a:focus-visible]:outline-secondary" aria-hidden="true">
                @svg('heroicon-o-shopping-cart', 'size-4') {{ Route::has('cart.add') ? __('Añadir a la cesta') : __('Ver producto') }}
            </span>
        </div>
    </div>
</article>
