{{-- Block "Franja de servicio" (Stitch: Top Seasonal Alert & Filter Strip). --}}
@php $categories = $block->payload['categories'] ?? collect(); @endphp
<section class="mx-auto w-full max-w-[1280px] px-4 pb-2 pt-4 lg:px-12">
    <div class="flex flex-col items-center justify-between gap-3 rounded-2xl border border-outline-variant/80 bg-white px-4 py-2.5 shadow-sm md:flex-row">
        <p class="flex items-center gap-2 text-on-surface">
            <span class="flex size-2.5 shrink-0 animate-pulse rounded-full bg-accent" aria-hidden="true"></span>
            @if ($block->has('label'))<span class="shrink-0 text-xs font-extrabold uppercase tracking-wider text-secondary">{{ $block->text('label') }}</span>@endif
            <span class="text-xs font-medium text-on-surface-variant sm:text-sm">{{ $block->text('text') }}</span>
        </p>
        @if ($block->get('show_categories', true) && $categories->isNotEmpty())
            <div class="no-scrollbar flex w-full items-center gap-1.5 overflow-x-auto py-1 md:w-auto">
                @if ($block->has('featured_label'))<span class="mr-1 shrink-0 text-xs font-bold uppercase tracking-wider text-outline">{{ $block->text('featured_label') }}</span>@endif
                @foreach ($categories as $category)
                    <a href="{{ route('store.category', $category->slug) }}" @class([
                        'shrink-0 rounded-full border border-outline-variant/60 bg-surface-container-low px-3 py-1 text-xs transition-all hover:bg-surface-container',
                        'font-bold text-primary shadow-sm' => $loop->first,
                        'font-semibold text-on-surface' => ! $loop->first,
                    ])>{{ $category->name }}</a>
                @endforeach
            </div>
        @endif
    </div>
</section>
