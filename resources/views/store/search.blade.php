@extends('store.layouts.app')

{{-- Variables: $term (string), $products (paginator, or null when the term is shorter than 2 characters)
     + the listing data of CatalogController::listingData() when there is a term. --}}

@section('title', $term ? __('Resultados para «:term»', ['term' => $term]) : __('Buscar'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <nav aria-label="{{ __('Ruta') }}" class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-on-surface-variant">
        <a href="{{ route('store.home') }}" class="hover:text-secondary">{{ __('Inicio') }}</a>
        @svg('heroicon-o-chevron-right', 'size-3 text-outline')
        <span aria-current="page" class="font-bold text-primary">{{ $term ? __('Búsqueda: «:term»', ['term' => $term]) : __('Buscar') }}</span>
    </nav>

    @if ($products === null)
        <div class="rounded-2xl bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Buscar productos') }}</h1>
            <p class="mt-2 text-on-surface-variant">{{ __('Escribe al menos 2 letras para buscar.') }}</p>
            <form action="{{ route('store.search') }}" method="get" role="search" class="mt-4 flex max-w-xl gap-2">
                <label for="search-page-q" class="sr-only">{{ __('Buscar') }}</label>
                <input id="search-page-q" type="search" name="q" value="{{ $term }}" minlength="2" maxlength="100" required
                       class="flex-1 rounded-full border-0 bg-surface-container-low px-4 py-2.5 text-sm text-primary focus:ring-2 focus:ring-secondary">
                <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-container">{{ __('Buscar') }}</button>
            </form>
            <p class="mt-4 text-sm"><a href="{{ route('store.categories') }}" class="font-bold text-secondary hover:underline">{{ __('Ver todas las categorías') }}</a></p>
        </div>
    @else
        @include('store.partials.listing', [
            'heading' => trans_choice(':count resultado|:count resultados', $products->total()),
            'subheading' => __('para «:term»', ['term' => $term]),
            'sectionMode' => 'filter',
            'emptyText' => $filters->isEmpty() ? __('No hemos encontrado productos. Prueba con otras palabras.') : __('Ningún producto coincide con estos filtros.'),
        ])
    @endif
@endsection
