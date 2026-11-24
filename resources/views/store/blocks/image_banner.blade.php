{{-- Block "Banner con imagen": photo with title, text and button over a dark overlay. --}}
@php
    $path = $block->get('image');
    $image = $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    $link = $block->url('button_url');
@endphp
<section class="mx-auto w-full max-w-[1280px] px-4 py-6 lg:px-12" aria-labelledby="{{ $block->key }}-title">
    <div class="relative overflow-hidden rounded-3xl bg-primary text-white shadow-md">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $block->text('image_alt') }}" loading="lazy" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/45 to-black/10" aria-hidden="true"></div>
        @endif
        <div class="relative z-10 flex flex-col items-start gap-4 p-6 sm:p-10 lg:max-w-3xl lg:p-14">
            <h2 id="{{ $block->key }}-title" class="text-3xl font-extrabold leading-tight sm:text-4xl">{{ $block->text('title') }}</h2>
            @if ($block->has('text'))<p class="max-w-xl text-base text-white/90 sm:text-lg">{{ $block->text('text') }}</p>@endif
            @if ($link && $block->has('button_label'))
                <a @include('store.blocks._link', ['url' => $link]) class="mt-2 inline-flex items-center gap-2 rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-primary shadow-lg transition-all hover:shadow-xl">
                    {{ $block->text('button_label') }} @svg('heroicon-o-arrow-right', 'size-5')
                </a>
            @endif
        </div>
    </div>
</section>
