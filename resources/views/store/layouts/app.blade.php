{{--
    Storefront layout. Design: Stitch "Inicio - Tot & Més Barcelona" (D:\Projects\eComm\design\totmes-inicio.html):
    header, announcement bar and footer follow the design markup.
    Marketing texts (announcement bar, store pill, value cards, footer columns...) are edited by staff in
    "Contenido → Cabecera y pie" ($layout, per language); interface texts use __() with lang/en.json and lang/ca.json.
    Contract: see docs/storefront-views.md. Child views set:
      @section('title'), @section('description'), @section('canonical'), @push('head'), @section('content')
    Views that need full-width bands set @section('fullBleed', true) and handle their own max width.
--}}
@php
    $locales = config('app.available_locales');
    $currentLocale = app()->getLocale();
    $hasCart = Route::has('cart.show');
    $navLink = 'block whitespace-nowrap py-1 transition-colors';
    $navIdle = 'text-on-surface-variant hover:text-primary';
    $navActive = 'border-b-2 border-secondary font-bold text-secondary';
    $footLink = 'transition-colors hover:text-secondary';
    ['announcement' => $announce, 'header' => $head, 'valueProps' => $valueProps, 'footer' => $foot] = $layout;
    // Links to /pagina/... only when that page is published; special values ([mapa], [whatsapp]...) only when resolvable.
    $linkUrl = function (?string $url) use ($pageSlugs) {
        if ($url && str_starts_with($url, '/pagina/')) {
            return in_array(\Illuminate\Support\Str::before(\Illuminate\Support\Str::after($url, '/pagina/'), '#'), $pageSlugs, true) ? $url : null;
        }

        return $url;
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ $store->store_name }}</title>
    {{-- Views always give a string (a null value would open a section that is never closed). --}}
    @php $metaDescription = trim($__env->yieldContent('description')); @endphp
    <meta name="description" content="{!! $metaDescription !== '' ? $metaDescription : e($store->tagline) !!}">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta name="theme-color" content="#1d3557">
    {{-- Brand icons (logo kit). --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon-180.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:site_name" content="{{ $store->store_name }}">
    <meta property="og:title" content="@yield('title', $store->store_name)">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:locale" content="{{ ['es' => 'es_ES', 'ca' => 'ca_ES', 'en' => 'en_GB'][$currentLocale] ?? 'es_ES' }}">
    @unless (str_contains($__env->yieldPushContent('head'), 'og:image'))
        {{-- Brand link preview (logo kit social/og-image); product pages push their own photo. --}}
        <meta property="og:image" content="{{ asset('brand/og-image-1200x630.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    @stack('head')
    @php $pageEditing = \App\Domain\Content\Blocks\PageEditing::active(); @endphp
    @vite(array_filter(['resources/css/app.css', 'resources/js/app.js', $pageEditing ? 'resources/js/page-editor-preview.js' : null]))
    @if ($pageEditing)
        <meta name="robots" content="noindex, nofollow">
    @endif
</head>
{{-- Campaign effect (snow, confetti…) runs on the home page only, never in the visual editor preview. --}}
<body @class(['flex min-h-screen flex-col bg-surface', 'pb-editing' => $pageEditing])
      @if ($campaign) style="--campaign-accent: {{ $campaign->accent_color }}" @endif
      @if ($campaign && request()->routeIs('store.home') && ! $pageEditing) data-campaign-effect="{{ $campaign->effect->value }}" @endif>
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:text-secondary focus:shadow-lg">
        {{ __('Saltar al contenido') }}
    </a>

    <header class="sticky top-0 z-50 bg-white/95 shadow-soft backdrop-blur-xl">
        {{-- Announcement bar (deep navy) --}}
        <div class="bg-primary px-4 py-2 text-white lg:px-12">
            <div class="mx-auto flex max-w-[1280px] items-center justify-between gap-3 text-xs sm:text-sm">
                <p class="flex min-w-0 items-center gap-2 overflow-hidden whitespace-nowrap">
                    <span class="flex min-w-0 items-center gap-1.5 font-bold">
                        @svg('heroicon-o-building-storefront', 'size-4 shrink-0 text-accent')
                        <span class="truncate">{{ $campaign?->headline ?: $announce->text('text') }}</span>
                    </span>
                    @if ($campaign?->subheadline || $announce->has('extra') || $announce->get('code'))
                        <span class="hidden text-white/40 md:inline" aria-hidden="true">|</span>
                        <span class="hidden truncate text-white/90 md:inline">
                            @if ($campaign?->subheadline)
                                {{ $campaign->subheadline }}
                            @else
                                {{ $announce->text('extra') }}
                                @if ($announce->get('code'))
                                    <span class="rounded border border-white/20 bg-white/10 px-2 py-0.5 font-bold uppercase tracking-wider text-white">{{ $announce->get('code') }}</span>
                                @endif
                            @endif
                        </span>
                    @endif
                </p>
                <div class="flex shrink-0 items-center gap-3">
                    @if ($announce->has('pill'))
                        <span class="hidden items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white lg:flex">
                            <span>{{ $announce->text('pill') }}</span>
                            <span class="inline-block size-2 animate-pulse rounded-full bg-emerald-400" aria-hidden="true"></span>
                        </span>
                    @endif
                    <nav aria-label="{{ __('Idioma') }}" class="flex items-center gap-0.5 rounded-full bg-white/10 p-0.5 text-xs font-bold">
                        @foreach ($locales as $code => $label)
                            @if ($code === $currentLocale)
                                <span class="rounded-full bg-white px-2 py-0.5 uppercase text-primary" aria-current="true" title="{{ $label }}">{{ $code }}</span>
                            @else
                                <a href="{{ route('locale.switch', $code) }}" hreflang="{{ $code }}" lang="{{ $code }}" class="rounded-full px-2 py-0.5 uppercase text-white/80 transition-colors hover:text-white" title="{{ $label }}">
                                    {{ $code }}<span class="sr-only"> — {{ $label }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        {{-- Main row --}}
        <div class="mx-auto flex h-20 max-w-[1280px] items-center justify-between gap-4 px-4 lg:px-12">
            <div class="flex shrink-0 items-center gap-4">
                <a href="{{ route('store.home') }}" class="flex items-center transition-opacity hover:opacity-95" aria-label="{{ __(':store, inicio', ['store' => $store->store_name]) }}">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $store->store_name }}" class="h-10 w-auto object-contain sm:h-12">
                    @else
                        @include('store.partials.logo', ['name' => $store->store_name])
                    @endif
                </a>
                @if ($head->has('store_pill') && ($pillUrl = $linkUrl($head->url('store_pill_url') ?? '#contacto')))
                    <a @include('store.blocks._link', ['url' => $pillUrl]) class="hidden items-center gap-1.5 rounded-xl border border-outline-variant/60 bg-surface-container-low px-3 py-2 text-xs font-semibold text-on-surface-variant transition-colors hover:bg-surface-container hover:text-primary xl:flex">
                        @svg('heroicon-o-building-storefront', 'size-[18px] text-secondary')
                        <span>{{ $head->text('store_pill') }}</span>
                        @svg('heroicon-o-chevron-down', 'size-4')
                    </a>
                @endif
            </div>

            <form action="{{ route('store.search') }}" method="get" role="search" class="hidden max-w-2xl flex-1 lg:block">
                <label for="q" class="sr-only">{{ __('Buscar productos') }}</label>
                <div class="relative flex items-center">
                    @svg('heroicon-o-magnifying-glass', 'pointer-events-none absolute left-3.5 size-5 text-outline')
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="{{ $head->text('search_placeholder') ?: __('Buscar productos…') }}"
                           class="w-full rounded-xl border border-outline-variant bg-surface-container-low py-2.5 pl-10 pr-28 text-sm text-on-surface shadow-sm transition-all placeholder:text-outline focus:border-primary focus:bg-white focus:outline-none">
                    <button type="submit" class="absolute right-1.5 flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-bold text-white transition-colors hover:bg-primary-container">
                        @svg('heroicon-o-adjustments-horizontal', 'size-4') <span>{{ __('Buscar') }}</span>
                    </button>
                </div>
            </form>

            <nav aria-label="{{ __('Cuenta y cesta') }}" class="flex shrink-0 items-center gap-2 sm:gap-3">
                @auth('web')
                    <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2 rounded-full border border-outline-variant/60 bg-surface-container-low py-1.5 pl-2 pr-3 transition-colors hover:bg-surface-container">
                        @svg('heroicon-o-user-circle', 'size-5 text-secondary')
                        <span class="flex flex-col text-left">
                            <span class="text-xs font-bold leading-tight text-primary">{{ __('Mi cuenta') }}</span>
                            <span class="hidden text-[10px] font-bold uppercase leading-none tracking-wider text-secondary sm:block">{{ __('Pedidos y datos') }}</span>
                        </span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-full border border-outline-variant/60 bg-surface-container-low py-1.5 pl-2 pr-3 transition-colors hover:bg-surface-container">
                        @svg('heroicon-o-user-circle', 'size-5 text-secondary')
                        <span class="flex flex-col text-left">
                            <span class="text-xs font-bold leading-tight text-primary">{{ __('Entrar') }}</span>
                            <span class="hidden text-[10px] font-bold uppercase leading-none tracking-wider text-secondary sm:block">{{ __('o crear cuenta') }}</span>
                        </span>
                    </a>
                @endauth
                @if ($hasCart)
                    @php $cart = app(\App\Domain\Orders\Cart\Cart::class); $cartCount = $cart->count(); @endphp
                    <a href="{{ route('cart.show') }}" class="relative inline-flex items-center gap-1.5 rounded-xl bg-secondary px-3 py-2.5 text-sm font-bold text-white shadow-cta transition-all hover:bg-secondary-hover hover:shadow-lg active:scale-[0.98] sm:px-4"
                       aria-label="{{ trans_choice('Ver carrito, :count artículo|Ver carrito, :count artículos', $cartCount) }}">
                        @svg('heroicon-o-shopping-cart', 'size-[18px]')
                        <span class="hidden sm:inline">{{ __('Ver Carrito') }}@if ($cartCount > 0) ({{ \App\Support\Money::format($cart->totals()->subtotal) }})@endif</span>
                        @if ($cartCount > 0)
                            <span class="absolute -right-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white sm:hidden" aria-hidden="true">{{ $cartCount }}</span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('store.home') }}#novedades" class="hidden items-center gap-1.5 rounded-xl bg-secondary px-4 py-2.5 text-sm font-bold text-white shadow-cta transition-all hover:bg-secondary-hover hover:shadow-lg active:scale-[0.98] sm:inline-flex">
                        @svg('heroicon-o-shopping-bag', 'size-[18px]') <span>{{ __('Ver novedades') }}</span>
                    </a>
                @endif
            </nav>
        </div>

        {{-- Mobile search. Checkout pages set @section('compactHeader') (Stitch mobile cart): no search or menu on phones. --}}
        @php $compactHeader = trim($__env->yieldContent('compactHeader')) !== ''; @endphp
        <form action="{{ route('store.search') }}" method="get" role="search" @class(['px-4 pb-3 lg:hidden', 'hidden' => $compactHeader])>
            <label for="q-mobile" class="sr-only">{{ __('Buscar productos') }}</label>
            <div class="relative flex items-center">
                @svg('heroicon-o-magnifying-glass', 'pointer-events-none absolute left-3.5 size-5 text-outline')
                <input id="q-mobile" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('Buscar productos…') }}"
                       class="w-full rounded-xl border border-outline-variant bg-surface-container-low py-2.5 pl-10 pr-3 text-sm text-on-surface placeholder:text-outline focus:border-primary focus:bg-white focus:outline-none">
            </div>
        </form>

        {{-- Category navigation --}}
        <div @class(['mx-auto max-w-[1280px] border-t border-outline-variant/60 px-4 lg:px-12', 'hidden lg:block' => $compactHeader])>
            <nav aria-label="{{ __('Categorías') }}">
                <ul class="no-scrollbar flex items-center gap-6 overflow-x-auto py-2.5 text-sm font-semibold">
                    <li>
                        <a href="{{ route('store.home') }}" @class([$navLink, request()->routeIs('store.home') ? $navActive : $navIdle]) @if (request()->routeIs('store.home')) aria-current="page" @endif>{{ __('Inicio') }}</a>
                    </li>
                    <li>
                        <a href="{{ route('store.categories') }}" @class([$navLink, request()->routeIs('store.categories') ? $navActive : $navIdle]) @if (request()->routeIs('store.categories')) aria-current="page" @endif>{{ __('Todas las categorías') }}</a>
                    </li>
                    @foreach ($menuCategories as $menuCategory)
                        @php $active = request()->routeIs('store.category') && request()->route('slug') === $menuCategory['slug']; @endphp
                        <li>
                            <a href="{{ route('store.category', $menuCategory['slug']) }}" @class([$navLink, $active ? $navActive : $navIdle]) @if ($active) aria-current="page" @endif>{{ $menuCategory['name'] }}</a>
                        </li>
                    @endforeach
                    <li>
                        <a href="{{ route('store.home') }}#novedades" class="flex items-center gap-1 whitespace-nowrap py-1 font-bold text-secondary transition-colors hover:underline">
                            🔥 {{ __('Novedades y Ofertas') }}
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenido" class="w-full flex-1">
        @if (session('status'))
            <div class="mx-auto max-w-[1280px] px-4 pt-4 lg:px-12">
                <p role="status" class="rounded-2xl bg-success-container/40 px-4 py-3 text-body-md text-on-success-fixed-variant">{{ session('status') }}</p>
            </div>
        @endif

        @hasSection('fullBleed')
            @yield('content')
        @else
            <div class="mx-auto max-w-[1280px] px-4 py-8 lg:px-12">
                @yield('content')
            </div>
        @endif
    </main>

    <footer class="w-full border-t border-outline-variant/70 bg-white text-on-surface">
        {{-- Value props --}}
        <div class="border-b border-outline-variant/60 bg-surface-container-low py-6">
            <ul @class(['mx-auto grid max-w-[1280px] grid-cols-1 gap-4 px-4 lg:px-12', 'md:grid-cols-3' => count($valueProps) >= 3, 'md:grid-cols-2' => count($valueProps) === 2])>
                @foreach ($valueProps as $prop)
                    <li class="flex items-center gap-3 rounded-xl border border-outline-variant/60 bg-white p-4 shadow-sm">
                        <span @class(['flex size-10 shrink-0 items-center justify-center rounded-full', $loop->index === 1 ? 'bg-primary/10 text-primary' : 'bg-secondary-container text-secondary']) aria-hidden="true">@svg(\App\Domain\Content\Blocks\BlockTypes::icon($prop->get('icon')), 'size-[22px]')</span>
                        <span>
                            <span class="block text-sm font-bold text-primary">{{ $prop->text('title') }}</span>
                            <span class="block text-xs text-outline">{{ $prop->text('text') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mx-auto grid max-w-[1280px] grid-cols-2 gap-8 px-4 py-12 md:grid-cols-5 lg:px-12">
            <div class="col-span-2">
                <p class="mb-4">@include('store.partials.logo', ['name' => $store->store_name, 'size' => 'sm'])</p>
                @if ($foot->has('about'))
                    <p class="mb-4 max-w-sm text-sm leading-relaxed text-on-surface-variant">{{ $foot->text('about') }}</p>
                @endif
                <div class="flex flex-wrap items-center gap-2">
                    @if ($foot->has('chip_location'))
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-outline-variant/60 bg-surface-container-low px-3 py-1 text-xs font-semibold text-primary">📍 {{ $foot->text('chip_location') }}</span>
                    @endif
                    @if ($foot->get('chip_web'))
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-secondary/20 bg-surface-container-low px-3 py-1 text-xs font-semibold text-secondary">🌐 {{ $foot->get('chip_web') }}</span>
                    @endif
                </div>
                <address id="contacto" class="mt-4 scroll-mt-48 space-y-1 text-xs not-italic text-on-surface-variant">
                    @if ($store->address_line)
                        <span class="block">{{ $store->fullAddress() }}</span>
                    @endif
                    @if ($store->email)
                        <a href="mailto:{{ $store->email }}" class="block {{ $footLink }}">{{ $store->email }}</a>
                    @endif
                    @if ($store->phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->phone) }}" class="block {{ $footLink }}">{{ $store->phone }}</a>
                    @endif
                </address>
            </div>

            @foreach ((new \App\Domain\Content\Blocks\Block('footer', ['columns' => $foot->get('columns', [])]))->items('columns') as $column)
                @php $links = collect($column->items('links'))->map(fn ($l) => ['label' => $l->text('label'), 'url' => $linkUrl($l->url('url'))])->filter(fn ($l) => $l['url'] && $l['label']); @endphp
                @if ($links->isNotEmpty())
                    <nav aria-labelledby="footer-col-{{ $loop->index }}">
                        <p id="footer-col-{{ $loop->index }}" class="mb-3 text-sm font-bold text-primary">{{ $column->text('title') }}</p>
                        <ul class="space-y-2 text-xs font-medium text-on-surface-variant">
                            @foreach ($links as $link)
                                <li><a @include('store.blocks._link', ['url' => $link['url']]) class="{{ $footLink }}">{{ $link['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            @endforeach

            @if ($footerPages !== [])
                <nav aria-labelledby="footer-legal">
                    <p id="footer-legal" class="mb-3 text-sm font-bold text-primary">{{ __('Legal') }}</p>
                    <ul class="space-y-2 text-xs font-medium text-on-surface-variant">
                        @foreach ($footerPages as $footerPage)
                            <li><a href="{{ route('store.page', $footerPage['slug']) }}" class="{{ $footLink }}">{{ $footerPage['title'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>

        <div class="border-t border-outline-variant/60 py-6">
            <div class="mx-auto flex max-w-[1280px] flex-col items-center justify-between gap-4 px-4 text-xs font-medium text-outline sm:flex-row lg:px-12">
                <p>
                    {{ $foot->text('copyright', ['[año]' => now()->year, '[empresa]' => $store->legal_name ?: $store->store_name]) }}
                    @if ($store->nif) · {{ __('NIF') }} {{ $store->nif }}@endif
                </p>
                <p class="flex items-center gap-4">
                    @if ($foot->has('payments_note'))
                        <span class="flex items-center gap-1 font-semibold text-primary">@svg('heroicon-o-lock-closed', 'size-4 text-secondary') {{ $foot->text('payments_note') }}</span>
                    @endif
                    @if ($foot->has('payments_note') && $foot->has('carriers_note'))<span aria-hidden="true">•</span>@endif
                    @if ($foot->has('carriers_note'))<span>{{ $foot->text('carriers_note') }}</span>@endif
                </p>
            </div>
        </div>
    </footer>

    @include('store.partials.whatsapp-button')
</body>
</html>
