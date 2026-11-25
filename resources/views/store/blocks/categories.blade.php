{{-- Block "Departamentos" (Stitch: Visual Categories Section). --}}
@php $categories = $block->payload['categories'] ?? collect(); @endphp
@if ($categories->isNotEmpty())
    <section id="categorias" class="mx-auto w-full max-w-[1280px] scroll-mt-48 px-4 py-8 lg:px-12" aria-labelledby="{{ $block->key }}-title">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                @if ($block->has('eyebrow'))<p class="text-xs font-extrabold uppercase tracking-wider text-secondary">{{ $block->editable('eyebrow') }}</p>@endif
                <h2 id="{{ $block->key }}-title" class="text-xl font-extrabold text-primary sm:text-3xl">{{ $block->editable('title') }}</h2>
            </div>
            @if ($block->has('link_label'))
                <a href="{{ route('store.category', $categories->first()->slug) }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-bold text-secondary transition-colors hover:text-secondary-hover">
                    <span class="sm:hidden">{{ __('Todos') }}</span><span class="hidden sm:inline">{{ $block->editable('link_label') }}</span> @svg('heroicon-o-arrow-right', 'size-[18px]')
                </a>
            @endif
        </div>
        <ul class="grid grid-cols-4 gap-2 sm:gap-3.5 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($categories as $category)
                @php
                    $subtitle = $category->children->isNotEmpty()
                        ? $category->children->take(3)->pluck('name')->implode(', ')
                        : \Illuminate\Support\Str::limit((string) $category->description, 40);
                @endphp
                <li>
                    <a href="{{ route('store.category', $category->slug) }}" class="group relative flex h-full flex-col items-center overflow-hidden rounded-2xl border border-outline-variant/60 bg-white p-2 text-center shadow-sm transition-all hover:shadow-md md:items-stretch md:p-2.5 md:text-left">
                        <span class="relative block size-12 overflow-hidden rounded-full bg-surface-container md:aspect-square md:size-auto md:w-full md:rounded-xl">
                            @if ($category->cover_url)
                                <img src="{{ $category->cover_url }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105">
                            @else
                                <span class="flex size-full items-center justify-center text-xl font-extrabold text-primary/30 md:text-[56px]" aria-hidden="true">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                            @endif
                            <span class="absolute inset-0 hidden bg-gradient-to-t from-primary/60 to-transparent md:block" aria-hidden="true"></span>
                        </span>
                        <span class="w-full pt-1.5 md:px-1 md:py-2.5">
                            <span class="block truncate text-[11px] font-bold text-primary transition-colors group-hover:text-secondary md:text-sm">{{ $category->name }}</span>
                            @if ($subtitle)<span class="hidden text-xs text-outline md:block">{{ $subtitle }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
