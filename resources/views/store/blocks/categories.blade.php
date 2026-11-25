{{-- Block "Departamentos" (Stitch: Visual Categories Section). --}}
@php $categories = $block->payload['categories'] ?? collect(); @endphp
@if ($categories->isNotEmpty())
    <section id="categorias" class="mx-auto w-full max-w-[1280px] scroll-mt-48 px-4 py-8 lg:px-12" aria-labelledby="{{ $block->key }}-title">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                @if ($block->has('eyebrow'))<p class="text-xs font-extrabold uppercase tracking-wider text-secondary">{{ $block->editable('eyebrow') }}</p>@endif
                <h2 id="{{ $block->key }}-title" class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $block->editable('title') }}</h2>
            </div>
            @if ($block->has('link_label'))
                <a href="{{ route('store.category', $categories->first()->slug) }}" class="hidden items-center gap-1 text-sm font-bold text-secondary transition-colors hover:text-secondary-hover sm:inline-flex">
                    <span>{{ $block->editable('link_label') }}</span> @svg('heroicon-o-arrow-right', 'size-[18px]')
                </a>
            @endif
        </div>
        <ul class="grid grid-cols-2 gap-3.5 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($categories as $category)
                @php
                    $subtitle = $category->children->isNotEmpty()
                        ? $category->children->take(3)->pluck('name')->implode(', ')
                        : \Illuminate\Support\Str::limit((string) $category->description, 40);
                @endphp
                <li>
                    <a href="{{ route('store.category', $category->slug) }}" class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-white p-2.5 shadow-sm transition-all hover:shadow-md">
                        <span class="relative block aspect-square w-full overflow-hidden rounded-xl bg-surface-container">
                            @if ($category->cover_url)
                                <img src="{{ $category->cover_url }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105">
                            @else
                                <span class="flex size-full items-center justify-center text-[56px] font-extrabold text-primary/30" aria-hidden="true">{{ mb_strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                            @endif
                            <span class="absolute inset-0 bg-gradient-to-t from-primary/60 to-transparent" aria-hidden="true"></span>
                        </span>
                        <span class="px-1 py-2.5">
                            <span class="block text-sm font-bold text-primary transition-colors group-hover:text-secondary">{{ $category->name }}</span>
                            @if ($subtitle)<span class="block text-xs text-outline">{{ $subtitle }}</span>@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif
