@extends('store.layouts.app')

{{-- Variables: $category (with parent, children) + the listing data of CatalogController::listingData(). --}}

@section('title', $category->seo_title ?: $category->name)
@section('description', (string) ($category->seo_description ?: $category->description))
@section('canonical', route('store.category', $category->slug))

@section('content')
    <nav aria-label="{{ __('Ruta') }}" class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-on-surface-variant">
        <a href="{{ route('store.home') }}" class="hover:text-secondary">{{ __('Inicio') }}</a>
        @svg('heroicon-o-chevron-right', 'size-3 text-outline')
        <a href="{{ route('store.categories') }}" class="hover:text-secondary">{{ __('Categorías') }}</a>
        @if ($category->parent)
            @svg('heroicon-o-chevron-right', 'size-3 text-outline')
            <a href="{{ route('store.category', $category->parent->slug) }}" class="hover:text-secondary">{{ $category->parent->name }}</a>
        @endif
        @svg('heroicon-o-chevron-right', 'size-3 text-outline')
        <span aria-current="page" class="font-bold text-primary">{{ $category->name }}</span>
    </nav>

    @include('store.partials.listing', [
        'heading' => $category->name,
        'subheading' => trans_choice(':count producto|:count productos', $products->total()),
        'intro' => $category->description,
        'sectionMode' => 'link',
        'emptyText' => $filters->isEmpty() ? __('Todavía no hay productos en esta categoría.') : __('Ningún producto coincide con estos filtros.'),
    ])
@endsection
