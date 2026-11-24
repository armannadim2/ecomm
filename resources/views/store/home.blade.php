@extends('store.layouts.app')

{{--
    Home page, built from page-builder blocks (admin: Contenido → Páginas → "Inicio").
    Design: Stitch "Inicio - Tot & Més Barcelona"; each block type has its view in store/blocks/.
    Variables: $blocks (list<App\Domain\Content\Blocks\Block>), $page (?Page, for SEO)
--}}

@if ($page?->seo_description)
    @section('description', $page->seo_description)
@endif

@section('fullBleed', true)

@section('content')
    @if (! collect($blocks)->contains(fn ($b) => $b->type === \App\Domain\Content\Blocks\BlockTypes::HERO))
        <h1 class="sr-only">{{ $store->store_name }}</h1>
    @endif

    @php $heroDone = false; @endphp
    @foreach ($blocks as $block)
        {{-- Only the first hero title is the page <h1>. --}}
        @php $level = ($block->type === \App\Domain\Content\Blocks\BlockTypes::HERO && ! $heroDone) ? 1 : 2; @endphp
        @include('store.blocks.'.$block->type, ['block' => $block, 'headingLevel' => $level])
        @php $heroDone = $heroDone || $block->type === \App\Domain\Content\Blocks\BlockTypes::HERO; @endphp
    @endforeach
@endsection
