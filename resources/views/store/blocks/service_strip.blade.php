{{-- Block "Franja de servicio" (Stitch: Top Seasonal Alert & Filter Strip). --}}
@php $categories = $block->payload['categories'] ?? collect(); @endphp
<section class="mx-auto w-full max-w-[1280px] px-4 pb-2 pt-4 lg:px-12">
    {{-- Phones (Stitch mobile home): compact green card with the shop address. --}}
    <div class="flex items-center justify-between gap-3 rounded-xl bg-primary px-3.5 py-2.5 text-white md:hidden">
        <p class="flex min-w-0 items-center gap-2.5">
            <span class="size-2.5 shrink-0 animate-pulse rounded-full bg-success" aria-hidden="true"></span>
            <span class="min-w-0">
                <span class="block truncate text-[13px] font-bold text-white">{{ __('Tienda física') }}@if ($store->address_line) · {{ $store->address_line }}@endif</span>
                @if ($block->has('label'))<span class="block truncate text-xs text-white/75">{{ $block->editable('label') }}</span>@endif
            </span>
        </p>
        <span class="shrink-0 rounded-full bg-white/10 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-accent-light">{{ __('Activo') }}</span>
    </div>
    <div class="hidden flex-col items-center justify-between gap-3 rounded-2xl md:flex border border-outline-variant/80 bg-white px-4 py-2.5 shadow-sm md:flex-row">
        <p class="flex items-center gap-2 text-on-surface">
            <span class="flex size-2.5 shrink-0 animate-pulse rounded-full bg-accent" aria-hidden="true"></span>
            @if ($block->has('label'))<span class="shrink-0 text-xs font-extrabold uppercase tracking-wider text-secondary">{{ $block->editable('label') }}</span>@endif
            <span class="text-xs font-medium text-on-surface-variant sm:text-sm">{{ $block->editable('text') }}</span>
        </p>
        @if ($block->get('show_categories', true) && $categories->isNotEmpty())
            <div class="no-scrollbar flex w-full items-center gap-1.5 overflow-x-auto py-1 md:w-auto">
                @if ($block->has('featured_label'))<span class="mr-1 shrink-0 text-xs font-bold uppercase tracking-wider text-outline">{{ $block->editable('featured_label') }}</span>@endif
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
