{{--
    Header cart button + mini cart popup (resources/js/cart.js). The button opens a short summary
    instead of the checkout page; "Finalizar compra" goes on to the cart/checkout. Without JavaScript
    the button is a plain link to the cart.
--}}
@php
    $cartSubtotal = $cartCount > 0 ? \App\Support\Money::format($cart->totals()->subtotal) : '';
    $cartLabel = trans_choice('Ver carrito, :count artículo|Ver carrito, :count artículos', $cartCount);
@endphp
<div class="relative"
     x-data="miniCart(@js(['count' => $cartCount, 'subtotal' => $cartSubtotal, 'label' => $cartLabel, 'summaryUrl' => route('cart.summary')]))"
     x-on:keydown.escape.window="if ($store.cart.open) { $store.cart.open = false; $refs.cartButton.focus() }"
     x-on:click.outside="$store.cart.open = false">
    <a href="{{ route('cart.show') }}" x-ref="cartButton"
       x-on:click.prevent="$store.cart.toggle()"
       aria-haspopup="dialog" aria-controls="mini-cesta" :aria-expanded="$store.cart.open.toString()"
       class="relative inline-flex items-center gap-1.5 rounded-xl bg-secondary px-3 py-2.5 text-sm font-bold text-white shadow-cta transition-all hover:bg-secondary-hover hover:shadow-lg active:scale-[0.98] sm:px-4"
       aria-label="{{ $cartLabel }}" :aria-label="$store.cart.label">
        @svg('heroicon-o-shopping-cart', 'size-[18px]')
        <span class="hidden sm:inline">{{ __('Ver Carrito') }}<span x-text="$store.cart.count > 0 ? ' (' + $store.cart.subtotal + ')' : ''">@if ($cartCount > 0) ({{ $cartSubtotal }})@endif</span></span>
        <span @class(['absolute -right-1.5 -top-1.5 size-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white sm:hidden', 'flex' => $cartCount > 0, 'hidden' => $cartCount === 0])
              :class="$store.cart.count > 0 ? 'flex' : 'hidden'" x-text="$store.cart.count" aria-hidden="true">{{ $cartCount }}</span>
    </a>

    <div id="mini-cesta" role="dialog" aria-modal="false" aria-labelledby="mini-cesta-titulo"
         x-cloak x-show="$store.cart.open"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-x-4 top-20 z-50 flex max-h-[calc(100vh-6rem)] flex-col overflow-hidden rounded-2xl border border-outline-variant bg-white text-left shadow-2xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96">
        <div class="flex items-center justify-between gap-3 border-b border-outline-variant/60 px-5 py-4">
            <h2 id="mini-cesta-titulo" class="text-label-lg font-black text-primary">
                {{ __('Tu cesta') }} <span class="font-bold text-outline" x-show="$store.cart.count > 0" x-text="'(' + $store.cart.count + ')'"></span>
            </h2>
            <button type="button" x-on:click="$store.cart.open = false; $refs.cartButton.focus()" class="rounded-lg p-1 text-outline hover:bg-surface hover:text-primary" aria-label="{{ __('Cerrar') }}">
                @svg('heroicon-o-x-mark', 'size-5')
            </button>
        </div>

        <p x-show="$store.cart.message" x-text="$store.cart.message" role="status"
           class="mx-5 mt-4 flex items-center gap-2 rounded-xl bg-success-container/40 px-3 py-2 text-body-sm font-bold text-on-success-fixed-variant"></p>
        <p x-show="$store.cart.error" x-text="$store.cart.error" role="alert"
           class="mx-5 mt-4 rounded-xl bg-error-container px-3 py-2 text-body-sm font-bold text-on-error-container"></p>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3">
            <p x-show="$store.cart.loading && !$store.cart.loaded" class="py-6 text-center text-body-sm text-outline">{{ __('Cargando…') }}</p>
            <p x-show="$store.cart.loaded && $store.cart.lines.length === 0" class="py-6 text-center text-body-md text-outline">{{ __('Tu cesta está vacía.') }}</p>
            <ul class="divide-y divide-outline-variant/40" x-show="$store.cart.lines.length > 0">
                <template x-for="line in $store.cart.lines" :key="line.url + line.variant">
                    <li class="flex items-center gap-3 py-3">
                        <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface">
                            <template x-if="line.image"><img :src="line.image" alt="" class="size-full object-cover"></template>
                            <template x-if="!line.image"><span>@svg('heroicon-o-photo', 'size-6 text-outline')</span></template>
                        </span>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a :href="line.url" class="truncate text-[13px] font-bold text-primary hover:text-secondary" x-text="line.name"></a>
                            <span class="truncate text-xs text-outline" x-text="(line.variant ? line.variant + ' · ' : '') + '× ' + line.quantity"></span>
                        </div>
                        <span class="shrink-0 text-sm font-extrabold text-primary" x-text="line.total"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div class="flex flex-col gap-3 border-t border-outline-variant/60 bg-surface px-5 py-4" x-show="$store.cart.count > 0">
            <p class="flex items-center justify-between text-body-md font-bold text-primary">
                <span>{{ __('Subtotal') }}</span><span x-text="$store.cart.subtotal"></span>
            </p>
            <p class="text-xs text-outline">{{ __('IVA incluido. El envío se calcula al finalizar la compra.') }}</p>
            <a href="{{ route('cart.show') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-secondary px-5 py-3 text-label-lg font-extrabold text-white shadow-cta transition-all hover:bg-secondary-hover active:scale-[0.98]">
                {{ __('Finalizar compra') }} @svg('heroicon-o-arrow-right', 'size-5')
            </a>
            <button type="button" x-on:click="$store.cart.open = false" class="text-sm font-bold text-primary hover:text-secondary">{{ __('Seguir comprando') }}</button>
        </div>
    </div>
</div>
