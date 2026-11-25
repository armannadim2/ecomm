@extends('store.layouts.app')

{{--
    All departments (Stitch "Categories", D:\Projects\eComm\design\totmes-categories.html).
    Variables: $departments list<array{category: Category (with children), count: int, featured: ?Product, cover: ?string}>
--}}

@section('title', __('Todas las categorías'))
@section('description', __('Todos los departamentos de :store: encuentra lo que necesitas para tu casa.', ['store' => $store->store_name]))
@section('canonical', route('store.categories'))

@section('content')
    @php
        use App\Support\Money;
        $subcategories = $departments->flatMap(fn ($d) => $d['category']->children)->take(16);
    @endphp

    <nav aria-label="{{ __('Ruta') }}" class="mb-4 flex items-center gap-1.5 text-xs text-on-surface-variant">
        <a href="{{ route('store.home') }}" class="hover:text-secondary">{{ __('Inicio') }}</a>
        @svg('heroicon-o-chevron-right', 'size-3 text-outline')
        <span aria-current="page" class="font-bold text-primary">{{ __('Categorías') }}</span>
    </nav>

    <section class="relative overflow-hidden rounded-3xl bg-primary px-5 py-8 text-white sm:px-10 sm:py-12">
        <div class="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-accent/20 blur-3xl" aria-hidden="true"></div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-accent-light">{{ __('Departamentos') }}</p>
        <h1 class="mt-2 max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Todo para tu casa, en un solo sitio') }}</h1>
        <p class="mt-3 max-w-xl text-sm text-white/80 sm:text-base">
            {{ __('Explora todos nuestros departamentos. Compra online y recoge en la tienda: :address.', ['address' => $store->address_line ?: $store->city]) }}
        </p>

        @if ($departments->count() > 1)
            <ul class="mt-6 flex flex-wrap gap-2" aria-label="{{ __('Ir a un departamento') }}">
                @foreach ($departments as $d)
                    <li><a href="#dep-{{ $d['category']->slug }}" class="block rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-bold text-white transition-colors hover:bg-white/20">{{ $d['category']->name }}</a></li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($departments->isEmpty())
        <p class="mt-6 rounded-2xl bg-white p-8 text-center text-on-surface-variant shadow-sm">{{ __('Todavía no hay categorías.') }}</p>
    @else
        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($departments as $d)
                @php
                    $category = $d['category'];
                    $featured = $d['featured'];
                    $fVariant = $featured?->variants->sortBy('price_cents')->first();
                    $fAvailable = $featured?->variants->contains(fn ($v) => ! $v->track_stock || $v->stock > 0);
                @endphp
                <article id="dep-{{ $category->slug }}" class="group flex scroll-mt-28 flex-col overflow-hidden rounded-2xl bg-white shadow-sm transition-shadow hover:shadow-md">
                    <div class="relative aspect-[16/9] bg-surface-container">
                        @if ($d['cover'])
                            <img src="{{ $d['cover'] }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @else
                            <div class="flex size-full items-center justify-center text-outline" aria-hidden="true">@svg('heroicon-o-squares-2x2', 'size-12')</div>
                        @endif
                        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-bold text-primary shadow-sm">
                            {{ trans_choice(':count producto|:count productos', $d['count']) }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-5">
                        <h2 class="text-xl font-extrabold text-primary">{{ $category->name }}</h2>
                        @if ($category->description)
                            <p class="line-clamp-3 text-sm text-on-surface-variant">{{ $category->description }}</p>
                        @endif

                        @if ($category->children->isNotEmpty())
                            <ul class="flex flex-wrap gap-1.5">
                                @foreach ($category->children->take(6) as $child)
                                    <li><a href="{{ route('store.category', $child->slug) }}" class="block rounded-full bg-surface-container-low px-2.5 py-1 text-xs font-semibold text-primary hover:bg-surface-container">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($featured && $fVariant)
                            <a href="{{ route('store.product', $featured->slug) }}" class="flex items-center gap-3 rounded-xl bg-surface-container-low p-2.5 transition-colors hover:bg-surface-container">
                                <span class="size-14 shrink-0 overflow-hidden rounded-lg bg-white">
                                    @if ($img = $featured->imageUrl('thumb') ?? $featured->imageUrl('medium'))
                                        <img src="{{ $img }}" alt="" loading="lazy" class="size-full object-cover">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('Novedad') }}</span>
                                    <span class="block truncate text-sm font-bold text-primary">{{ $featured->name }}</span>
                                    <span class="flex items-center gap-2 text-xs">
                                        <span class="font-extrabold text-primary">{{ Money::format($fVariant->price_cents) }}</span>
                                        <span @class(['font-semibold', 'text-success' => $fAvailable, 'text-outline' => ! $fAvailable])>{{ $fAvailable ? __('En stock') : __('Agotado') }}</span>
                                    </span>
                                </span>
                            </a>
                        @endif

                        <a href="{{ route('store.category', $category->slug) }}" class="mt-auto inline-flex items-center justify-center gap-2 rounded-full bg-secondary px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-secondary-hover">
                            {{ __('Explorar :name', ['name' => $category->name]) }} @svg('heroicon-o-arrow-right', 'size-4')
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($subcategories->isNotEmpty())
            <section class="mt-8 rounded-2xl bg-white p-5 shadow-sm">
                <h2 class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-sparkles', 'size-5 text-accent') {{ __('Subcategorías') }}</h2>
                <ul class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($subcategories as $sub)
                        <li><a href="{{ route('store.category', $sub->slug) }}" class="flex items-center justify-between rounded-xl bg-surface-container-low px-3 py-2.5 text-sm font-semibold text-primary hover:bg-surface-container">{{ $sub->name }} @svg('heroicon-o-chevron-right', 'size-4 text-outline')</a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endif
@endsection
