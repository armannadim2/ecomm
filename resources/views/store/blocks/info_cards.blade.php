{{-- Block "Tarjetas informativas" (Stitch: Visit our physical shop). --}}
@php $link = $block->url('link_url'); $cards = $block->items('cards'); @endphp
<section class="mx-auto w-full max-w-[1280px] px-4 py-8 lg:px-12" aria-labelledby="{{ $block->key }}-title">
    <div class="mb-6 flex items-end justify-between gap-4">
        <div>
            @if ($block->has('eyebrow'))<p class="text-xs font-extrabold uppercase tracking-wider text-secondary">{{ $block->editable('eyebrow') }}</p>@endif
            <h2 id="{{ $block->key }}-title" class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $block->editable('title') }}</h2>
        </div>
        @if ($link && $block->has('link_label'))
            <a @include('store.blocks._link', ['url' => $link]) class="hidden items-center gap-1 text-sm font-bold text-secondary transition-colors hover:text-secondary-hover sm:inline-flex">
                <span>{{ $block->editable('link_label') }}</span> @svg('heroicon-o-arrow-right', 'size-[18px]')
            </a>
        @endif
    </div>
    <div @class(['grid grid-cols-1 gap-6', 'md:grid-cols-3' => count($cards) >= 3, 'md:grid-cols-2' => count($cards) === 2])>
        @foreach ($cards as $card)
            <article class="flex flex-col justify-between rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
                <div class="flex flex-col gap-3">
                    <span @class(['flex size-12 items-center justify-center rounded-2xl', ['bg-secondary-container text-secondary', 'bg-primary/10 text-primary', 'bg-accent/15 text-secondary'][$loop->index % 3]]) aria-hidden="true">@svg(\App\Domain\Content\Blocks\BlockTypes::icon($card->get('icon')), 'size-7')</span>
                    <h3 class="text-lg font-bold text-primary">{{ $card->editable('title') }}</h3>
                    @if ($card->has('text'))<p class="text-sm text-on-surface-variant">{{ $card->editable('text') }}</p>@endif
                    @if ($card->has('highlight_title') || $card->has('highlight_text'))
                        <div class="rounded-xl border border-outline-variant/40 bg-surface-container-low p-3">
                            @if ($card->has('highlight_title'))<p @class(['text-xs font-bold', $loop->index === 1 ? 'text-primary' : 'text-secondary'])>{{ $card->editable('highlight_title') }}</p>@endif
                            @if ($card->has('highlight_text'))<p class="mt-0.5 text-xs text-outline">{{ $card->editable('highlight_text') }}</p>@endif
                        </div>
                    @endif
                </div>
                @if ($card->has('footer'))
                    <p class="mt-3 flex items-center gap-1 border-t border-outline-variant/40 pt-4 text-xs font-medium text-outline">@svg(\App\Domain\Content\Blocks\BlockTypes::icon($card->get('footer_icon')), 'size-4 text-secondary') {{ $card->editable('footer') }}</p>
                @endif
            </article>
        @endforeach
    </div>
</section>
