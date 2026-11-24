@extends('store.layouts.app')

{{-- Variables: $term (string), $products (paginator, or null when the term is shorter than 2 characters) --}}

@section('title', $term ? __('Resultados para «:term»', ['term' => $term]) : __('Buscar'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">
        @if ($term) {{ __('Resultados para «:term»', ['term' => $term]) }} @else {{ __('Buscar productos') }} @endif
    </h1>

    @if ($products === null)
        <p class="mt-4 text-on-surface-variant">{{ __('Escribe al menos 2 letras para buscar.') }}</p>
    @elseif ($products->isEmpty())
        <p class="mt-4 text-on-surface-variant">{{ __('No hemos encontrado productos. Prueba con otras palabras.') }}</p>
    @else
        <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 lg:gap-6">
            @foreach ($products as $product)
                @include('store.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @endif
@endsection
