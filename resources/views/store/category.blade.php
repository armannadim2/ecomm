@extends('store.layouts.app')

{{-- Variables: $category (with parent, children), $products (paginator), $sorts (key => label), $currentSort --}}

@section('title', $category->seo_title ?: $category->name)
@section('description', $category->seo_description ?: $category->description)
@section('canonical', route('store.category', $category->slug))

@section('content')
    <nav aria-label="{{ __('Ruta') }}" class="mb-4 text-xs text-on-surface-variant">
        <a href="{{ route('store.home') }}" class="hover:text-secondary">{{ __('Inicio') }}</a>
        @if ($category->parent)
            <span class="text-outline-variant">/</span> <a href="{{ route('store.category', $category->parent->slug) }}" class="hover:text-secondary">{{ $category->parent->name }}</a>
        @endif
        <span class="text-outline-variant">/</span> <span aria-current="page" class="font-semibold text-primary">{{ $category->name }}</span>
    </nav>

    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $category->name }}</h1>
    @if ($category->description)
        <p class="mt-2 max-w-2xl text-sm text-on-surface-variant">{{ $category->description }}</p>
    @endif

    @if ($category->children->isNotEmpty())
        <ul class="mt-4 flex flex-wrap gap-2">
            @foreach ($category->children as $child)
                <li><a href="{{ route('store.category', $child->slug) }}" class="block rounded-xl border border-outline-variant/60 bg-surface-container-low px-3.5 py-1.5 text-xs font-bold text-primary hover:bg-surface-container">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <form method="get" class="my-6 flex items-center gap-2 border-b border-outline-variant/60 pb-4">
        <label for="orden" class="text-sm font-semibold text-primary">{{ __('Ordenar por') }}</label>
        <select id="orden" name="orden" onchange="this.form.submit()" class="rounded-xl border border-outline-variant bg-white px-3 py-1.5 text-sm text-primary">
            @foreach ($sorts as $key => $label)
                <option value="{{ $key }}" @selected($key === $currentSort)>{{ __($label) }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="rounded-xl border border-outline-variant bg-white px-4 py-2 text-sm font-bold text-primary transition-colors hover:bg-surface-container-low">{{ __('Aplicar') }}</button></noscript>
    </form>

    @if ($products->isEmpty())
        <p class="rounded-2xl border border-outline-variant/60 bg-white p-8 text-center text-on-surface-variant">{{ __('Todavía no hay productos en esta categoría.') }}</p>
    @else
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            @foreach ($products as $product)
                @include('store.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
@endsection
