@extends('store.layouts.app')

{{--
    Content page. Variables: $page (Page), $body (sanitised HTML with store data filled in),
    $blocks (page-builder blocks shown after the text, list<App\Domain\Content\Blocks\Block>).
    The page body itself is not translated yet; block texts are (es / ca / en).
--}}

@section('title', $page->localizedTitle())
@section('description', (string) $page->seo_description)
@section('canonical', route('store.page', $page->slug))
@section('fullBleed', true)

@section('content')
    @if (filled(strip_tags($body)) || $blocks === [])
        <div class="mx-auto max-w-[1280px] px-4 py-8 lg:px-12">
            <article class="rich-text mx-auto max-w-3xl rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-10">
                <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $page->localizedTitle() }}</h1>
                {!! $body !!}
                <p class="text-xs text-outline">{{ __('Última actualización: :date', ['date' => $page->updated_at->timezone('Europe/Madrid')->format('d/m/Y')]) }}</p>
            </article>
        </div>
    @else
        <h1 class="sr-only">{{ $page->title }}</h1>
    @endif

    @foreach ($blocks as $block)
        @include('store.blocks._render', ['block' => $block, 'headingLevel' => 2])
    @endforeach
@endsection
