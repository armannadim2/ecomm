{{--
    Return conditions, taken from the legal page "Envíos, recogida y devoluciones" (App\Domain\Content\LegalTexts):
    14 days to withdraw, 3-year legal guarantee, return to the shop at no cost, refund within 14 days.
    Variables: $store.
--}}
@php
    $hours = (new \App\Domain\Content\Blocks\Block('hours', ['h' => app(\App\Domain\Orders\Settings\CheckoutSettings::class)->opening_hours]))->text('h');
@endphp
<div class="grid gap-3 sm:grid-cols-3">
    <div class="flex gap-3 rounded-2xl bg-surface-container-low p-4">
        @svg('heroicon-o-calendar-days', 'size-6 shrink-0 text-accent')
        <div>
            <h3 class="text-sm font-bold text-primary">{{ __('14 días para devolver') }}</h3>
            <p class="text-xs text-on-surface-variant">{{ __('Desde que recoges o recibes el pedido, sin dar explicaciones.') }}</p>
        </div>
    </div>
    <div class="flex gap-3 rounded-2xl bg-surface-container-low p-4">
        @svg('heroicon-o-banknotes', 'size-6 shrink-0 text-accent')
        <div>
            <h3 class="text-sm font-bold text-primary">{{ __('Reembolso en 14 días') }}</h3>
            <p class="text-xs text-on-surface-variant">{{ __('Con el mismo medio de pago que usaste, desde que nos comunicas tu decisión.') }}</p>
        </div>
    </div>
    <div class="flex gap-3 rounded-2xl bg-surface-container-low p-4">
        @svg('heroicon-o-shield-check', 'size-6 shrink-0 text-accent')
        <div>
            <h3 class="text-sm font-bold text-primary">{{ __('Garantía legal de 3 años') }}</h3>
            <p class="text-xs text-on-surface-variant">{{ __('Si el producto es defectuoso o no es el que pediste, sin gastos para ti.') }}</p>
        </div>
    </div>
</div>
<div class="mt-4 rounded-2xl border border-outline-variant/60 p-4 text-sm text-on-surface-variant">
    <h3 class="font-bold text-primary">{{ __('Cómo funciona') }}</h3>
    <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>{{ __('Cuando aprobemos tu solicitud te avisaremos por correo. Entonces trae el producto a la tienda, sin coste.') }}@if ($store->address_line) {{ __('Estamos en :address.', ['address' => $store->fullAddress()]) }}@endif @if (filled($hours)) {{ __('Horario: :hours.', ['hours' => $hours]) }}@endif</li>
        <li>{{ __('Devuélvelo en buen estado, con sus accesorios y, a ser posible, en su embalaje.') }}</li>
        <li>{{ __('No se pueden devolver por desistimiento: productos personalizados, productos de higiene precintados que ya se hayan abierto, productos que caducan con rapidez y grabaciones o programas precintados que ya se hayan abierto.') }}</li>
    </ul>
    <p class="mt-3"><a href="{{ route('store.page', 'envios-y-devoluciones') }}" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Leer las condiciones completas') }}</a></p>
</div>
