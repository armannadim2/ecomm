@extends('store.layouts.app')

{{--
    Product page (Tot & Més design: D:\Projects\eComm\design\totmes-detalle-producto.html).
    Variables: $product (brand, categories, variants, media loaded), $variants, $images (media collection),
    $related (up to 4 products).
    The add-to-cart form is shown once the cart exists (route "cart.add").
--}}

@php
    use App\Support\Money;

    $variantData = $variants->map(fn ($v) => [
        'id' => $v->ulid,
        'name' => $v->name ?: __('Única'),
        'sku' => $v->sku,
        'price' => Money::format($v->price_cents),
        'compare' => $v->compare_at_price_cents > $v->price_cents ? Money::format($v->compare_at_price_cents) : null,
        'discount' => $v->compare_at_price_cents > $v->price_cents
            ? (int) round(100 - $v->price_cents * 100 / $v->compare_at_price_cents)
            : null,
        'available' => ! $v->track_stock || $v->stock > 0,
        'lowStock' => $v->track_stock && $v->stock > 0 && $v->stock <= $v->low_stock_threshold,
    ])->values();
    $first = $variantData->firstWhere('available', true) ?? $variantData->first();

    $photos = $images->map(fn ($m) => [
        'src' => $m->hasGeneratedConversion('large') ? $m->getUrl('large') : $m->getUrl(),
        'thumb' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl(),
    ])->values();

    $category = $product->categories->first();
@endphp

@section('title', $product->seo_title ?: $product->name)
@section('description', (string) ($product->seo_description ?: $product->short_description))
@section('canonical', route('store.product', $product->slug))
@section('fullBleed', true)

@push('head')
    @if ($product->imageUrl('large'))
        <meta property="og:image" content="{{ $product->imageUrl('large') }}">
    @endif
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => strip_tags((string) ($product->short_description ?: $product->description)),
        'image' => $images->map(fn ($m) => $m->getUrl())->values(),
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
        'offers' => $variants->map(fn ($v) => [
            '@type' => 'Offer',
            'sku' => $v->sku,
            'price' => number_format($v->price_cents / 100, 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability' => (! $v->track_stock || $v->stock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => route('store.product', $product->slug),
        ])->values(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    {{-- Breadcrumb bar --}}
    <div class="w-full border-b border-outline-variant/60 bg-surface py-2.5">
        <nav aria-label="{{ __('Ruta') }}" class="mx-auto max-w-[1280px] px-5 lg:px-12">
            <ol class="flex flex-wrap items-center gap-2 text-body-sm text-outline">
                <li><a href="{{ route('store.home') }}" class="flex items-center gap-1 transition-colors hover:text-secondary">@svg('heroicon-o-home', 'size-4')<span>{{ __('Inicio') }}</span></a></li>
                @if ($category)
                    <li aria-hidden="true" class="font-medium text-outline-variant">/</li>
                    <li><a href="{{ route('store.category', $category->slug) }}" class="transition-colors hover:text-secondary">{{ $category->name }}</a></li>
                @endif
                <li aria-hidden="true" class="font-medium text-outline-variant">/</li>
                <li aria-current="page" class="max-w-[50vw] truncate rounded border border-outline-variant bg-white px-2 py-0.5 font-semibold text-primary">{{ $product->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="mx-auto w-full max-w-[1280px] px-5 py-8 lg:px-12"
         x-data="{ variants: @js($variantData), selected: @js($first['id'] ?? null), photo: 0,
            get current() { return this.variants.find(v => v.id === this.selected) } }">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            {{-- LEFT: gallery + description --}}
            <div class="flex flex-col gap-6 lg:col-span-7">
                <section aria-label="{{ __('Fotos del producto') }}" class="flex flex-col gap-3">
                    <div class="relative w-full overflow-hidden rounded-2xl border border-outline-variant bg-white shadow-md">
                        @forelse ($photos as $photo)
                            <img src="{{ $photo['src'] }}" alt="{{ $product->name }}@if ($photos->count() > 1) ({{ __('foto :n de :total', ['n' => $loop->iteration, 'total' => $photos->count()]) }})@endif"
                                 class="aspect-square w-full object-cover"
                                 @if (! $loop->first) x-cloak loading="lazy" @endif
                                 x-show="photo === {{ $loop->index }}">
                        @empty
                            <div class="flex aspect-square w-full items-center justify-center bg-surface-container text-outline" aria-hidden="true">@svg('heroicon-o-photo', 'size-16')</div>
                        @endforelse

                        <span class="absolute left-4 top-4 z-10 flex items-center gap-1.5 rounded-full bg-primary/90 px-3.5 py-1.5 text-label-sm uppercase tracking-wide text-white shadow-md backdrop-blur-md">
                            <span class="size-2 rounded-full" :class="current?.available ? 'bg-emerald-400' : 'bg-red-400'" aria-hidden="true"></span>
                            <span x-text="current?.available ? {{ Js::from(__('En stock')) }} : {{ Js::from(__('Agotado')) }}">{{ ($first['available'] ?? false) ? __('En stock') : __('Agotado') }}</span>
                        </span>
                    </div>

                    @if ($photos->count() > 1)
                        <div class="grid grid-cols-5 gap-3">
                            @foreach ($photos as $photo)
                                <button type="button" x-on:click="photo = {{ $loop->index }}"
                                        class="aspect-square overflow-hidden rounded-xl border border-outline-variant bg-white transition-all active:scale-95"
                                        :class="photo === {{ $loop->index }} ? 'ring-2 ring-secondary' : 'opacity-70 hover:opacity-100'"
                                        :aria-pressed="(photo === {{ $loop->index }}).toString()"
                                        aria-label="{{ __('Ver foto :n', ['n' => $loop->iteration]) }}">
                                    <img src="{{ $photo['thumb'] }}" alt="" loading="lazy" class="size-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if ($product->description || $product->short_description)
                    <section class="mt-2 flex flex-col gap-4 rounded-2xl border border-outline-variant bg-white p-6 shadow-sm" aria-labelledby="descripcion-titulo">
                        <h2 id="descripcion-titulo" class="border-b border-outline-variant/60 pb-3 text-title-lg font-extrabold text-primary">{{ __('Descripción y características') }}</h2>
                        @if ($product->short_description)
                            <p class="text-body-lg text-on-surface-variant">{{ $product->short_description }}</p>
                        @endif
                        @if ($product->description)
                            <div class="rich-text">{!! \Illuminate\Support\Str::sanitizeHtml($product->description) !!}</div>
                        @endif
                    </section>
                @endif

                <div class="flex items-center gap-4 rounded-2xl border-2 border-outline-variant bg-surface p-5 shadow-sm">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary text-accent" aria-hidden="true">@svg('heroicon-o-check-badge', 'size-7')</span>
                    <div>
                        <h2 class="text-label-lg font-extrabold text-primary">{{ __('Compra tranquilo') }}</h2>
                        <p class="text-body-sm text-on-surface-variant">{!! __('Si no te convence, tienes :days desde que lo recibes.', ['days' => '<strong>'.e(__('14 días naturales para devolverlo')).'</strong>']) !!}</p>
                    </div>
                </div>
            </div>

            {{-- RIGHT: buy box --}}
            <div class="flex flex-col gap-6 lg:sticky lg:top-44 lg:col-span-5">
                <section class="flex flex-col gap-6 rounded-2xl border border-outline-variant bg-white p-6 shadow-lg sm:p-7" aria-label="{{ __('Comprar') }}">
                    <div>
                        @if ($product->brand || $category)
                            <p class="mb-2 flex flex-wrap items-center gap-2">
                                @if ($product->brand)
                                    <span class="rounded-full bg-secondary px-2.5 py-0.5 text-label-sm uppercase tracking-wider text-white">{{ $product->brand->name }}</span>
                                @endif
                                @if ($category)
                                    <span class="text-label-sm font-semibold text-on-surface-variant">{{ $category->name }}</span>
                                @endif
                            </p>
                        @endif
                        <h1 class="text-[24px] font-black leading-snug text-primary">{{ $product->name }}</h1>
                        <p class="mt-1 text-body-sm text-outline" x-show="current?.sku">{{ __('Ref.') }} <span x-text="current?.sku">{{ $first['sku'] ?? '' }}</span></p>
                    </div>

                    <div class="flex flex-wrap items-baseline justify-between gap-2 rounded-xl border border-outline-variant bg-surface p-4">
                        <p class="flex items-baseline gap-3">
                            <strong class="text-[30px] font-black leading-9 tracking-tight text-primary" x-text="current?.price">{{ $first['price'] ?? '' }}</strong>
                            <del class="text-title-md text-outline" x-show="current?.compare" x-text="current?.compare" @if (! ($first['compare'] ?? null)) x-cloak @endif>{{ $first['compare'] ?? '' }}</del>
                            <span class="block text-body-sm text-outline">{{ __('IVA incluido') }}</span>
                        </p>
                        <span class="rounded-full bg-secondary px-2.5 py-1 text-label-sm font-black text-white shadow-sm"
                              x-show="current?.discount" x-text="'-' + current?.discount + '%'" x-cloak></span>
                    </div>

                    @if ($variants->count() > 1)
                        <fieldset>
                            <legend class="mb-2 text-label-lg font-bold text-primary">{{ __('Elige una opción') }}</legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($variantData as $v)
                                    <label class="cursor-pointer rounded-xl border-2 px-3.5 py-2 text-label-lg transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-secondary"
                                           :class="selected === '{{ $v['id'] }}' ? 'border-secondary bg-secondary-container/60 text-secondary' : 'border-outline-variant text-primary hover:border-outline'">
                                        <input type="radio" name="variant" value="{{ $v['id'] }}" x-model="selected" class="sr-only"
                                               @disabled(! $v['available'])>
                                        {{ $v['name'] }} @unless ($v['available']) <span class="text-outline">({{ __('agotado') }})</span> @endunless
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <p class="flex items-center gap-2 text-body-md font-medium" aria-live="polite">
                        <span class="size-2 rounded-full" :class="!current?.available ? 'bg-error' : (current?.lowStock ? 'bg-warning' : 'bg-success')" aria-hidden="true"></span>
                        <span class="text-error" x-show="current && !current.available" x-cloak>{{ __('Agotado') }}</span>
                        <span class="text-warning" x-show="current?.lowStock" x-cloak>{{ __('¡Últimas unidades!') }}</span>
                        <span class="text-success" x-show="current?.available && !current?.lowStock">{{ __('En stock') }}</span>
                    </p>

                    @if (Route::has('cart.add'))
                        <form method="post" action="{{ route('cart.add') }}" class="flex flex-col gap-3" x-data="addToCart" x-on:submit.prevent="submit">
                            @csrf
                            <input type="hidden" name="variant" :value="selected">
                            <div class="flex items-center gap-4 rounded-xl border border-outline-variant bg-surface p-3">
                                <label for="quantity" class="text-label-md font-bold text-primary">{{ __('Cantidad') }}</label>
                                <input id="quantity" type="number" name="quantity" value="1" min="1" max="99"
                                       class="w-20 rounded-lg border border-outline-variant bg-white px-3 py-1.5 text-center text-body-md font-bold">
                            </div>
                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-6 py-4 text-label-lg font-extrabold text-on-secondary shadow-cta transition-all hover:bg-secondary-hover hover:shadow-xl active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="busy || !current?.available">
                                <span x-show="!added" class="flex items-center gap-2">@svg('heroicon-o-shopping-bag', 'size-5') {{ __('Añadir a la cesta') }}</span>
                                <span x-cloak x-show="added" class="flex items-center gap-2">@svg('heroicon-o-check', 'size-5') {{ __('Añadido a la cesta') }}</span>
                            </button>
                        </form>
                    @endif

                    <ul class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-label-sm text-outline">
                        <li class="flex items-center gap-1">@svg('heroicon-o-lock-closed', 'size-3.5') {{ __('Pago seguro') }}</li>
                        <li aria-hidden="true">•</li>
                        <li class="flex items-center gap-1">@svg('heroicon-o-truck', 'size-3.5') {{ __('Envío a toda España') }}</li>
                        <li aria-hidden="true">•</li>
                        <li class="flex items-center gap-1">@svg('heroicon-o-arrow-path-rounded-square', 'size-3.5') {{ __('14 días de devolución') }}</li>
                    </ul>
                </section>

                <section class="flex flex-col gap-4 rounded-2xl border border-outline-variant bg-white p-6 shadow-sm" aria-labelledby="vendedor-titulo">
                    <div class="flex items-center gap-3">
                        <span class="flex size-12 items-center justify-center rounded-xl bg-primary text-accent shadow-sm" aria-hidden="true">@svg('heroicon-o-building-storefront', 'size-7')</span>
                        <div>
                            <h2 id="vendedor-titulo" class="text-label-lg font-black text-primary">{{ __('Vendido y gestionado por :store', ['store' => $store->store_name]) }}</h2>
                            @if ($store->city)
                                <p class="text-body-sm font-bold text-secondary">{{ __('Tienda española · :city', ['city' => $store->city]) }}</p>
                            @endif
                        </div>
                    </div>
                    <ul class="grid grid-cols-1 gap-2 text-body-sm text-on-surface-variant sm:grid-cols-2">
                        <li class="flex items-center gap-1.5">@svg('heroicon-o-truck', 'size-[18px] text-primary') {{ __('Envíos a toda España') }}</li>
                        <li class="flex items-center gap-1.5">@svg('heroicon-o-shield-check', 'size-[18px] text-secondary') {{ __('Garantía legal de 3 años') }}</li>
                        @if ($store->email)
                            <li class="flex items-center gap-1.5">@svg('heroicon-o-envelope', 'size-[18px] text-secondary') <a href="mailto:{{ $store->email }}" class="hover:text-secondary">{{ $store->email }}</a></li>
                        @endif
                        @if ($store->phone)
                            <li class="flex items-center gap-1.5">@svg('heroicon-o-phone', 'size-[18px] text-secondary') <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->phone) }}" class="hover:text-secondary">{{ $store->phone }}</a></li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="w-full border-y border-outline-variant bg-surface py-12" aria-labelledby="relacionados-titulo">
            <div class="mx-auto max-w-[1280px] px-5 lg:px-12">
                <div class="mb-6 flex items-center justify-between gap-4">
                    <div>
                        <h2 id="relacionados-titulo" class="text-title-lg font-black text-primary">{{ __('También te puede interesar') }}</h2>
                        <p class="text-body-sm text-outline">{{ __('Otros productos parecidos de la tienda.') }}</p>
                    </div>
                    @if ($category)
                        <a href="{{ route('store.category', $category->slug) }}" class="flex items-center gap-1 text-label-lg font-bold text-secondary transition-colors hover:text-secondary-hover">
                            {{ __('Ver :category', ['category' => $category->name]) }} @svg('heroicon-o-arrow-right', 'size-[18px]')
                        </a>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        @include('store.partials.product-card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
