{{-- Block "Texto libre": formatted text written in the rich editor (sanitised before output). --}}
<section class="mx-auto w-full max-w-[1280px] px-4 py-6 lg:px-12">
    <div @class(['rich-text rounded-3xl border border-outline-variant/60 bg-white p-6 shadow-sm sm:p-10', 'mx-auto max-w-3xl' => $block->get('width', 'narrow') === 'narrow'])>
        @if ($block->has('title'))<h2 class="text-2xl font-extrabold text-primary">{{ $block->text('title') }}</h2>@endif
        {!! \Illuminate\Support\Str::sanitizeHtml($block->text('body')) !!}
    </div>
</section>
