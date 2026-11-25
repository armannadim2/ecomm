{{-- Block "Banner de ventajas" (Stitch: Trust / Buyer Protection Banner). --}}
@php $link = $block->url('link_url'); $cards = $block->items('cards'); @endphp
<section class="mx-auto w-full max-w-[1280px] px-4 py-8 lg:px-12" aria-labelledby="{{ $block->key }}-title">
    <div class="relative overflow-hidden rounded-3xl border border-outline-variant/70 bg-surface-container-low p-8 shadow-sm sm:p-10">
        <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-12">
            <div @class(['flex flex-col gap-3', 'lg:col-span-5' => $cards !== [], 'lg:col-span-12' => $cards === []])>
                @if ($block->has('badge'))
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-secondary-container px-3 py-1 text-xs font-bold text-secondary">@svg('heroicon-o-check-badge', 'size-4') {{ $block->editable('badge') }}</span>
                @endif
                <h2 id="{{ $block->key }}-title" class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $block->editable('title') }}</h2>
                @if ($block->has('text'))<p class="text-sm leading-relaxed text-on-surface-variant">{{ $block->editable('text') }}</p>@endif
                @if ($link && $block->has('link_label') && (! str_starts_with($link, '/pagina/') || in_array(\Illuminate\Support\Str::after($link, '/pagina/'), $pageSlugs, true)))
                    <p class="pt-2">
                        <a @include('store.blocks._link', ['url' => $link]) class="inline-flex items-center gap-1.5 text-sm font-bold text-secondary hover:underline">
                            <span>{{ $block->editable('link_label') }}</span> @svg('heroicon-o-chevron-right', 'size-[18px]')
                        </a>
                    </p>
                @endif
            </div>
            @if ($cards !== [])
                <ul @class(['grid grid-cols-1 gap-4 lg:col-span-7', 'sm:grid-cols-3' => count($cards) >= 3, 'sm:grid-cols-2' => count($cards) === 2])>
                    @foreach ($cards as $card)
                        <li class="flex flex-col gap-2.5 rounded-2xl border border-outline-variant/60 bg-white p-5 shadow-sm">
                            <span @class(['flex size-10 items-center justify-center rounded-xl', ['bg-secondary-container text-secondary', 'bg-accent/15 text-secondary', 'bg-primary/10 text-primary'][$loop->index % 3]]) aria-hidden="true">@svg(\App\Domain\Content\Blocks\BlockTypes::icon($card->get('icon')), 'size-[22px]')</span>
                            <h3 class="text-sm font-bold text-primary">{{ $card->editable('title') }}</h3>
                            <p class="text-xs text-outline">{{ $card->editable('text') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
