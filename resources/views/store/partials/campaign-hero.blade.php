{{--
    Campaign hero for the home page (Tot & Més editorial hero). Expects: $campaign (live Campaign).
    The visual effect itself is started by the layout (data-campaign-effect on <body>).
    With a banner image the text sits on a dark overlay; without one, on the campaign accent colour.
--}}
@php $banner = $campaign->bannerUrl(); @endphp
<section class="campaign-hero relative overflow-hidden rounded-3xl text-white shadow-md"
         style="background-color: {{ $campaign->accent_color }};"
         aria-labelledby="campaign-title">
    @if ($banner)
        <img src="{{ $banner }}" alt="" class="absolute inset-0 size-full object-cover" fetchpriority="high">
        <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/45 to-black/10" aria-hidden="true"></div>
    @else
        <div class="pointer-events-none absolute -right-20 -top-20 size-96 rounded-full bg-white/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-12 size-80 rounded-full bg-black/15 blur-3xl" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-black/20" aria-hidden="true"></div>
    @endif

    <div class="relative z-10 flex flex-col items-start gap-4 p-6 sm:p-10 lg:max-w-3xl lg:p-14">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-label-sm uppercase tracking-wider text-on-surface shadow-sm">
            @svg('heroicon-o-sparkles', 'size-4 text-secondary') {{ __('Campaña especial') }}
        </span>
        <h2 id="campaign-title" class="font-display text-headline-display-mobile leading-[1.1] tracking-tight sm:text-headline-display">{{ $campaign->headline }}</h2>
        @if ($campaign->subheadline)
            <p class="max-w-xl text-body-lg text-white/90">{{ $campaign->subheadline }}</p>
        @endif

        @if ($campaign->show_countdown)
            <p class="inline-flex items-center gap-2 rounded-2xl bg-black/30 px-4 py-2 text-label-lg backdrop-blur-sm"
               data-countdown="{{ $campaign->ends_at->toIso8601String() }}"
               data-countdown-prefix="{{ __('Termina en') }}" data-countdown-done="{{ __('¡Últimas horas!') }}" aria-live="off">
                {{ __('Termina el :date', ['date' => $campaign->ends_at->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LLL')]) }}
            </p>
        @endif

        @if ($campaign->cta_label && $campaign->cta_url)
            <a href="{{ $campaign->cta_url }}" class="mt-2 inline-flex items-center gap-2 rounded-2xl bg-white px-6 py-3.5 text-label-lg text-on-surface shadow-lg transition-all hover:shadow-xl active:scale-[0.98]">
                {{ $campaign->cta_label }} @svg('heroicon-o-arrow-right', 'size-5')
            </a>
        @endif
    </div>
</section>
