{{-- Customer service card (Stitch "El Meu Compte": "Atenció al Veí Directa") with the real shop contact data. --}}
@php
    $waUrl = \App\Domain\Content\Blocks\Block::resolveUrl('[whatsapp]');
    $hours = (new \App\Domain\Content\Blocks\Block('hours', ['h' => app(\App\Domain\Orders\Settings\CheckoutSettings::class)->opening_hours]))->text('h');
@endphp
<section class="rounded-2xl bg-primary p-5 text-white shadow-sm">
    <h2 class="flex items-center gap-2 text-base font-bold">@svg('heroicon-o-lifebuoy', 'size-5 text-accent-light') {{ __('¿Necesitas ayuda?') }}</h2>
    <p class="mt-1 text-sm text-white/80">{{ __('Dudas con un pedido, un cambio o un producto: habla directamente con la tienda.') }}</p>
    <ul class="mt-4 flex flex-col gap-2 text-sm">
        @if ($waUrl)
            <li>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="flex items-center justify-between gap-2 rounded-xl bg-white/10 px-3 py-2.5 font-bold hover:bg-white/20">
                    <span class="flex items-center gap-2">@svg('heroicon-o-chat-bubble-oval-left', 'size-5') {{ __('WhatsApp') }}</span>
                    <span class="text-white/80">{{ $store->phone }}</span>
                    <span class="sr-only">{{ __('(se abre en una pestaña nueva)') }}</span>
                </a>
            </li>
        @endif
        @if ($store->phone)
            <li>
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->phone) }}" class="flex items-center justify-between gap-2 rounded-xl bg-white/10 px-3 py-2.5 font-bold hover:bg-white/20">
                    <span class="flex items-center gap-2">@svg('heroicon-o-phone', 'size-5') {{ __('Llamar') }}</span>
                    <span class="text-white/80">{{ $store->phone }}</span>
                </a>
            </li>
        @endif
        @if ($store->email)
            <li>
                <a href="mailto:{{ $store->email }}" class="flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2.5 font-bold hover:bg-white/20">@svg('heroicon-o-envelope', 'size-5') <span class="truncate">{{ $store->email }}</span></a>
            </li>
        @endif
    </ul>
    @if ($hours)
        <p class="mt-3 flex items-start gap-2 text-xs text-white/80">@svg('heroicon-o-clock', 'size-4 shrink-0') {{ $hours }}</p>
    @endif
</section>
