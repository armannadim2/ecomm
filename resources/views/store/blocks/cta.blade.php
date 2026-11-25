{{-- Block "Llamada a la acción" (Stitch: Community / slogan banner). Style navy or cream. --}}
@php $primary = $block->url('primary_url'); $secondary = $block->url('secondary_url'); $navy = $block->get('style', 'navy') === 'navy'; @endphp
<section class="mx-auto w-full max-w-[1280px] px-4 pb-12 pt-6 lg:px-12" aria-labelledby="{{ $block->key }}-title">
    <div @class(['relative flex flex-col items-center justify-center overflow-hidden rounded-3xl p-8 text-center shadow-md sm:p-12', 'bg-primary text-white' => $navy, 'border border-outline-variant/70 bg-surface-container-low text-primary' => ! $navy])>
        <span @class(['mb-4 flex size-16 items-center justify-center rounded-full border', 'border-white/20 bg-white/10' => $navy, 'border-outline-variant bg-white text-secondary' => ! $navy]) aria-hidden="true">@svg(\App\Domain\Content\Blocks\BlockTypes::icon($block->get('icon', 'archive-box')), 'size-8')</span>
        <h2 id="{{ $block->key }}-title" class="mb-3 max-w-2xl text-2xl font-extrabold sm:text-3xl">{{ $block->editable('title') }}</h2>
        @if ($block->has('text'))<p @class(['mb-6 max-w-xl text-sm leading-relaxed sm:text-base', 'text-white/80' => $navy, 'text-on-surface-variant' => ! $navy])>{{ $block->editable('text') }}</p>@endif
        <div class="flex flex-wrap items-center justify-center gap-3">
            @if ($primary && $block->has('primary_label'))
                <a @include('store.blocks._link', ['url' => $primary]) class="flex items-center gap-2 rounded-2xl bg-secondary px-8 py-3.5 text-sm font-bold text-white shadow-lg transition-all hover:bg-secondary-hover hover:shadow-xl">
                    @svg('heroicon-o-shopping-cart', 'size-5') <span>{{ $block->editable('primary_label') }}</span>
                </a>
            @endif
            @if ($secondary && $block->has('secondary_label'))
                <a @include('store.blocks._link', ['url' => $secondary]) class="flex items-center gap-2 rounded-2xl border border-outline-variant/40 bg-white px-6 py-3.5 text-sm font-bold text-primary shadow-sm transition-all hover:shadow">
                    @svg('heroicon-o-chat-bubble-left-right', 'size-5 text-secondary') <span>{{ $block->editable('secondary_label') }}</span>
                </a>
            @endif
        </div>
    </div>
</section>
