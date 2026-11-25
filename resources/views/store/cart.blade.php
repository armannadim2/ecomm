@extends('store.layouts.app')

{{--
    Cart + checkout on one page. Design: Stitch "Cistella i Pagament - Tot & Més Barcelona" (desktop + mobile).
    Variables: $lines (Collection<CartLine>), $totals (['pickup' => CartTotals, 'delivery' => CartTotals]),
    $fulfillment (Fulfillment), $settings (CheckoutSettings), $paymentMethods (list<PaymentMethod>),
    $customer (?Customer), $address (?Address), $crossSell (Collection<Product>)
    Online payments (Bizum, card, PayPal) and home delivery show as "coming soon" until their gateway is connected.
--}}

@php
    use App\Domain\Orders\Enums\Fulfillment;
    use App\Domain\Orders\Enums\PaymentMethod;
    use App\Support\Money;

    $pickup = $totals['pickup'];
    $delivery = $totals['delivery'];
    $hours = (new \App\Domain\Content\Blocks\Block('checkout', ['h' => $settings->opening_hours, 'n' => $settings->pickup_note]));
    $whatsapp = \App\Domain\Content\Blocks\Block::resolveUrl('[whatsapp]');
    $defaultPayment = old('payment_method', collect($paymentMethods)->first(fn ($m) => $m->isAvailable())?->value);
    $card = 'rounded-xl bg-white p-4 shadow-sm ring-1 ring-outline-variant/60 md:p-6';
    $input = 'w-full rounded-xl border border-outline-variant bg-surface-container-low px-3.5 py-3 text-sm font-medium text-primary placeholder:text-outline focus:border-secondary focus:bg-white focus:outline-none';
    $label = 'text-[13px] font-bold text-primary';
@endphp

@section('title', __('Mi cesta'))
@section('fullBleed', true)
@section('compactHeader', true)

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="mx-auto w-full max-w-[1360px] px-4 py-4 md:px-8 lg:px-12 lg:py-6"
         x-data="{ fulfillment: @js($fulfillment->value), payment: @js($defaultPayment) }">

        {{-- Top bar: back link + steps --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('store.home') }}" class="inline-flex items-center gap-1.5 text-[13px] font-semibold text-on-surface-variant transition-colors hover:text-primary">
                @svg('heroicon-o-arrow-left', 'size-[18px]') <span>{{ __('Seguir comprando') }}</span>
            </a>
            @if ($lines->isNotEmpty())
                <nav aria-label="{{ __('Pasos del pedido') }}" class="flex items-center gap-1 rounded-full bg-surface-container-low px-2 py-1.5 text-[13px] font-semibold">
                    <a href="#cesta" class="rounded-full bg-primary px-4 py-1.5 font-bold text-white shadow-sm">1. {{ __('Cesta') }}</a>
                    @svg('heroicon-o-chevron-right', 'size-3.5 text-outline-variant')
                    <a href="#datos" class="rounded-full px-4 py-1.5 text-on-surface-variant hover:text-primary">2. {{ __('Datos y entrega') }}</a>
                    @svg('heroicon-o-chevron-right', 'size-3.5 text-outline-variant')
                    <a href="#pago" class="rounded-full px-4 py-1.5 text-on-surface-variant hover:text-primary">3. {{ __('Pago seguro') }}</a>
                </nav>
            @endif
        </div>

        @if ($errors->hasAny(['cart', 'checkout']))
            <div role="alert" class="mb-6 rounded-xl border border-error/30 bg-error-container px-4 py-3 text-sm font-semibold text-on-error-container">
                {{ $errors->first('checkout') ?: $errors->first('cart') }}
            </div>
        @endif

        @if ($lines->isEmpty())
            {{-- Empty cart --}}
            <section class="mx-auto flex max-w-xl flex-col items-center gap-4 rounded-3xl border border-outline-variant/60 bg-white p-10 text-center shadow-sm">
                <span class="flex size-16 items-center justify-center rounded-full bg-secondary-container text-secondary" aria-hidden="true">@svg('heroicon-o-shopping-bag', 'size-8')</span>
                <h1 class="text-2xl font-extrabold text-primary">{{ __('Tu cesta está vacía') }}</h1>
                <p class="text-sm text-on-surface-variant">{{ __('Descubre las novedades de la tienda y añade lo que te guste.') }}</p>
                <a href="{{ route('store.home') }}#novedades" class="mt-2 inline-flex items-center gap-2 rounded-xl bg-secondary px-6 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">
                    @svg('heroicon-o-sparkles', 'size-5') {{ __('Ver novedades') }}
                </a>
            </section>
        @else
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                {{-- LEFT: the order process --}}
                <div class="flex flex-col gap-6 lg:col-span-8">
                    <div class="flex flex-row items-end justify-between gap-2">
                        <div>
                            <div class="mb-1 hidden flex-wrap items-center gap-2 sm:flex">
                                <span class="rounded-full bg-secondary-container px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('Cesta verificada') }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-container px-2.5 py-0.5 text-[11px] font-bold text-on-success-fixed-variant">
                                    <span class="size-2 rounded-full bg-success" aria-hidden="true"></span>{{ __('En stock') }}
                                </span>
                            </div>
                            <h1 class="text-2xl font-extrabold tracking-tight text-primary sm:text-[32px] sm:leading-10"><span class="sm:hidden">{{ __('Mi cesta') }}</span><span class="hidden sm:inline">{{ __('Mi cesta y pago seguro') }}</span></h1>
                        </div>
                        <span class="whitespace-nowrap rounded-lg bg-surface-container-low px-3 py-1.5 text-xs font-bold text-on-surface-variant sm:text-sm">
                            {{ trans_choice(':count artículo seleccionado|:count artículos seleccionados', $pickup->itemCount) }}
                        </span>
                    </div>

                    {{-- Free delivery banner --}}
                    <div class="relative flex items-center justify-between gap-4 overflow-hidden rounded-xl bg-success-container p-3 text-on-success-fixed-variant sm:bg-primary sm:p-4 sm:text-white sm:shadow-md">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl sm:size-12 sm:bg-accent/20 sm:text-accent" aria-hidden="true">@svg('heroicon-o-gift', 'size-7')</span>
                            <div>
                                @if (! $settings->delivery_enabled || $delivery->missingForFreeDelivery === 0)
                                    <p class="text-[13px] font-bold sm:text-[15px]">{{ $settings->delivery_enabled ? __('¡Genial! Ya tienes envío o recogida GRATIS') : __('Recogida en tienda GRATIS') }}</p>
                                    <p class="text-xs sm:text-white/70">
                                        @if ($settings->delivery_enabled)
                                            {{ __('Superas los :threshold necesarios con un importe actual de :amount.', ['threshold' => Money::format($settings->free_delivery_threshold_cents), 'amount' => Money::format($pickup->subtotal)]) }}
                                        @else
                                            {{ __('Pide online y recoge tu compra en nuestra tienda sin gastos de envío.') }}
                                        @endif
                                    </p>
                                @else
                                    <p class="text-[13px] font-bold sm:text-[15px]">{{ __('Te faltan :amount para el envío gratis', ['amount' => Money::format($delivery->missingForFreeDelivery)]) }}</p>
                                    <p class="text-xs sm:text-white/70">{{ __('La recogida en tienda siempre es gratis.') }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="hidden shrink-0 items-center gap-2 rounded-lg bg-white/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-secondary-container sm:flex">
                            @svg('heroicon-o-check-badge', 'size-[18px]') {{ __('Envío :amount', ['amount' => Money::format(0)]) }}
                        </span>
                    </div>

                    {{-- 1. Cart items --}}
                    <section id="cesta" class="{{ $card }} flex scroll-mt-48 flex-col gap-4" aria-labelledby="cesta-titulo">
                        <div class="flex items-center justify-between">
                            <h2 id="cesta-titulo" class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-shopping-bag', 'size-6 text-secondary') {{ __('Artículos de la tienda') }}</h2>
                            <form method="post" action="{{ route('cart.clear') }}" onsubmit="return confirm({{ Js::from(__('¿Vaciar la cesta?')) }})">
                                @csrf @method('delete')
                                <button type="submit" class="flex items-center gap-1 text-[13px] font-semibold text-on-surface-variant transition-colors hover:text-error">
                                    @svg('heroicon-o-trash', 'size-4') <span>{{ __('Vaciar cesta') }}</span>
                                </button>
                            </form>
                        </div>

                        @foreach ($lines as $line)
                            @php
                                $image = $line->product->imageUrl('thumb');
                                $discount = $line->compareAt() ? (int) round(100 - $line->unitPrice() * 100 / $line->compareAt()) : null;
                                $lowStock = $line->variant->track_stock && $line->variant->stock <= $line->variant->low_stock_threshold;
                            @endphp
                            <article class="flex flex-col items-start justify-between gap-4 rounded-xl bg-surface-container-low/60 p-4 transition-colors hover:bg-surface-container-low sm:flex-row sm:items-center">
                                <div class="flex min-w-0 flex-1 items-start gap-4 sm:items-center">
                                    <a href="{{ route('store.product', $line->product->slug) }}" class="relative flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-surface-container-high sm:size-24">
                                        @if ($image)
                                            <img src="{{ $image }}" alt="{{ $line->product->name }}" class="size-full object-cover">
                                        @else
                                            @svg('heroicon-o-photo', 'size-8 text-outline')
                                        @endif
                                        @if ($discount)
                                            <span class="absolute left-1 top-1 rounded bg-secondary px-1.5 py-0.5 text-[10px] font-bold text-white">-{{ $discount }}%</span>
                                        @endif
                                    </a>
                                    <div class="flex min-w-0 flex-1 flex-col">
                                        <div class="mb-1 flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-[11px] text-on-surface-variant">{{ __('Ref.') }} {{ $line->variant->sku }}</span>
                                            <span @class(['inline-flex items-center gap-1 rounded-full px-2 text-[11px] font-bold', 'bg-success-container text-on-success-fixed-variant' => ! $lowStock, 'bg-secondary-container text-secondary' => $lowStock])>
                                                <span @class(['size-1.5 rounded-full', 'bg-success' => ! $lowStock, 'bg-secondary' => $lowStock]) aria-hidden="true"></span>
                                                {{ $lowStock ? __('¡Últimas unidades!') : __('En stock') }}
                                            </span>
                                        </div>
                                        <h3 class="text-base font-bold leading-snug text-primary">
                                            <a href="{{ route('store.product', $line->product->slug) }}" class="hover:text-secondary">{{ $line->product->name }}</a>
                                        </h3>
                                        @if ($line->variant->name)
                                            <p class="text-xs text-on-surface-variant">{{ $line->variant->name }}</p>
                                        @elseif ($line->product->short_description)
                                            <p class="line-clamp-1 text-xs text-on-surface-variant">{{ $line->product->short_description }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex w-full items-center justify-between gap-6 sm:w-auto sm:justify-end">
                                    {{-- Quantity stepper (each button submits the new quantity) --}}
                                    <form method="post" action="{{ route('cart.update', $line->variant->ulid) }}" class="inline-flex items-center rounded-lg bg-white p-1 shadow-sm ring-1 ring-outline-variant/60">
                                        @csrf @method('patch')
                                        <button type="submit" name="quantity" value="{{ $line->quantity - 1 }}" class="flex size-8 items-center justify-center rounded text-primary hover:bg-surface-container-high" aria-label="{{ __('Quitar una unidad de :product', ['product' => $line->product->name]) }}">
                                            @svg('heroicon-o-minus', 'size-4')
                                        </button>
                                        <span class="w-9 text-center text-[15px] font-bold text-primary" aria-label="{{ __('Cantidad') }}">{{ $line->quantity }}</span>
                                        <button type="submit" name="quantity" value="{{ $line->quantity + 1 }}" @disabled($line->quantity >= $line->maxQuantity()) class="flex size-8 items-center justify-center rounded text-primary hover:bg-surface-container-high disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Añadir una unidad de :product', ['product' => $line->product->name]) }}">
                                            @svg('heroicon-o-plus', 'size-4')
                                        </button>
                                    </form>
                                    <div class="flex min-w-[90px] flex-col items-end">
                                        <span class="text-2xl font-black tracking-tight text-primary">{{ Money::format($line->total()) }}</span>
                                        @if ($line->compareAt())
                                            <del class="text-xs text-outline"><span class="sr-only">{{ __('Antes') }} </span>{{ Money::format($line->compareAt() * $line->quantity) }}</del>
                                        @endif
                                    </div>
                                    <form method="post" action="{{ route('cart.remove', $line->variant->ulid) }}">
                                        @csrf @method('delete')
                                        <button type="submit" class="rounded-lg p-2 text-outline transition-colors hover:bg-error-container/40 hover:text-error" aria-label="{{ __('Eliminar :product', ['product' => $line->product->name]) }}">
                                            @svg('heroicon-o-trash', 'size-5')
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </section>

                    <form id="checkout-form" method="post" action="{{ route('checkout.place') }}" class="flex flex-col gap-6" novalidate>
                        @csrf
                        <input type="hidden" name="fulfillment" :value="fulfillment" value="{{ $fulfillment->value }}">

                        {{-- 2. Delivery method --}}
                        <section class="{{ $card }} flex flex-col gap-4" aria-labelledby="entrega-titulo">
                            <div class="flex items-center justify-between gap-3">
                                <h2 id="entrega-titulo" class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-truck', 'size-6 text-secondary') {{ __('¿Cómo quieres recibir tu compra?') }}</h2>
                                <span class="rounded-full bg-secondary-container px-2.5 py-1 text-[11px] font-bold uppercase text-secondary">{{ __('Servicio inmediato') }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2" role="radiogroup" aria-label="{{ __('Forma de entrega') }}">
                                @foreach ([Fulfillment::Pickup, Fulfillment::Delivery] as $option)
                                    @php $enabled = $option === Fulfillment::Pickup ? $settings->pickup_enabled : $settings->delivery_enabled; @endphp
                                    <label @class(['relative flex flex-col rounded-xl p-4 shadow-sm ring-2 transition-all', 'cursor-pointer hover:shadow-md' => $enabled, 'cursor-not-allowed opacity-60' => ! $enabled])
                                           :class="fulfillment === '{{ $option->value }}' ? 'bg-surface-container-low ring-secondary' : 'bg-white ring-transparent'">
                                        <input type="radio" class="sr-only" value="{{ $option->value }}" x-model="fulfillment" @disabled(! $enabled)
                                               x-on:change="if (fulfillment !== 'pickup' && payment === 'pay_in_store') payment = null">
                                        <span class="mb-1 flex items-start justify-between gap-2">
                                            <span class="flex items-center gap-2">
                                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full" :class="fulfillment === '{{ $option->value }}' ? 'bg-secondary text-white' : 'bg-surface-container-high'" aria-hidden="true">
                                                    <span x-show="fulfillment === '{{ $option->value }}'">@svg('heroicon-o-check', 'size-4')</span>
                                                </span>
                                                <span class="text-base font-bold leading-tight text-primary">
                                                    {{ $option === Fulfillment::Pickup ? __('Recogida exprés en tienda') : __('Envío a domicilio 24–48 h') }}
                                                </span>
                                            </span>
                                            <span class="shrink-0 rounded-full bg-success-container px-2 py-0.5 text-[11px] font-extrabold text-on-success-fixed-variant">
                                                @if (! $enabled)
                                                    {{ __('Próximamente') }}
                                                @elseif ($option === Fulfillment::Pickup)
                                                    {{ __('GRATIS') }}
                                                @elseif ($delivery->shipping === 0)
                                                    {{ __('GRATIS') }}
                                                @else
                                                    {{ Money::format($delivery->shipping) }}
                                                @endif
                                            </span>
                                        </span>
                                        <span class="mt-1 flex flex-col gap-2 pl-8">
                                            @if ($option === Fulfillment::Pickup)
                                                <span class="text-sm font-medium text-on-surface">{{ $store->address_line ? $store->fullAddress() : __('En nuestra tienda física') }}</span>
                                                @if ($hours->has('n'))
                                                    <span class="flex items-center gap-1.5 rounded-lg bg-success-container/60 px-2.5 py-1 text-[11px] font-semibold text-on-success-fixed-variant">@svg('heroicon-o-clock', 'size-4') {{ $hours->text('n') }}</span>
                                                @endif
                                                @if ($hours->has('h'))
                                                    <span class="flex items-center gap-2 text-xs text-on-surface-variant">@svg('heroicon-o-building-storefront', 'size-4 text-secondary') {{ $hours->text('h') }}</span>
                                                @endif
                                            @else
                                                <span class="text-sm text-on-surface">{{ __('A cualquier dirección de la Península y Baleares') }}</span>
                                                <span class="flex items-center gap-1.5 rounded-lg bg-surface-container-high/60 px-2.5 py-1 text-[11px] text-on-surface-variant">
                                                    @svg('heroicon-o-truck', 'size-4 text-primary')
                                                    @if ($enabled && $settings->free_delivery_threshold_cents > 0)
                                                        {{ __('Gratis desde :amount', ['amount' => Money::format($settings->free_delivery_threshold_cents)]) }}
                                                    @elseif (! $enabled)
                                                        {{ __('Disponible cuando activemos el pago online') }}
                                                    @else
                                                        {{ __('Seguimiento del envío por correo') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('fulfillment') <p class="text-sm text-error">{{ $message }}</p> @enderror

                            {{-- Delivery address --}}
                            <div x-show="fulfillment === 'delivery'" x-cloak class="grid grid-cols-1 gap-4 border-t border-outline-variant/60 pt-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="ship_line1" class="{{ $label }}">{{ __('Dirección') }} <span class="text-secondary">*</span></label>
                                    <input id="ship_line1" name="ship_line1" value="{{ old('ship_line1', $address?->line1) }}" autocomplete="address-line1" class="{{ $input }} mt-1.5" @error('ship_line1') aria-invalid="true" @enderror>
                                    @error('ship_line1') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="ship_line2" class="{{ $label }}">{{ __('Piso, puerta…') }} <span class="font-normal text-outline">({{ __('opcional') }})</span></label>
                                    <input id="ship_line2" name="ship_line2" value="{{ old('ship_line2', $address?->line2) }}" autocomplete="address-line2" class="{{ $input }} mt-1.5">
                                </div>
                                <div>
                                    <label for="ship_postal_code" class="{{ $label }}">{{ __('Código postal') }} <span class="text-secondary">*</span></label>
                                    <input id="ship_postal_code" name="ship_postal_code" value="{{ old('ship_postal_code', $address?->postal_code) }}" inputmode="numeric" maxlength="5" autocomplete="postal-code" class="{{ $input }} mt-1.5" @error('ship_postal_code') aria-invalid="true" @enderror>
                                    @error('ship_postal_code') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="ship_city" class="{{ $label }}">{{ __('Población') }} <span class="text-secondary">*</span></label>
                                    <input id="ship_city" name="ship_city" value="{{ old('ship_city', $address?->city) }}" autocomplete="address-level2" class="{{ $input }} mt-1.5" @error('ship_city') aria-invalid="true" @enderror>
                                    @error('ship_city') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </section>

                        {{-- 3. Customer data --}}
                        <section id="datos" class="{{ $card }} flex scroll-mt-48 flex-col gap-4" aria-labelledby="datos-titulo">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 id="datos-titulo" class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-identification', 'size-6 text-secondary') {{ __('Tus datos para el pedido') }}</h2>
                                <span class="text-[11px] text-on-surface-variant">{{ __('Tus datos viajan cifrados') }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                @foreach ([
                                    ['first_name', __('Nombre'), 'text', 'given-name', old('first_name', $customer?->first_name ?? $address?->first_name), true, 'heroicon-o-user'],
                                    ['last_name', __('Apellidos'), 'text', 'family-name', old('last_name', $customer?->last_name ?? $address?->last_name), true, 'heroicon-o-user'],
                                    ['phone', __('Teléfono móvil'), 'tel', 'tel', old('phone', $customer?->phone ?? $address?->phone), true, 'heroicon-o-device-phone-mobile'],
                                    ['email', __('Correo electrónico'), 'email', 'email', old('email', $customer?->email), true, 'heroicon-o-at-symbol'],
                                    ['tax_id', __('NIF / DNI'), 'text', 'off', old('tax_id', $address?->tax_id), false, 'heroicon-o-document-text'],
                                ] as [$name, $text, $type, $auto, $value, $required, $icon])
                                    <div class="flex flex-col gap-1.5">
                                        <label for="{{ $name }}" class="{{ $label }} flex items-center justify-between gap-2">
                                            <span>{{ $text }} @if ($required)<span class="text-secondary">*</span>@endif</span>
                                            @if ($name === 'tax_id')<span class="text-[11px] font-normal text-on-surface-variant">{{ __('Opcional, para la factura') }}</span>@endif
                                        </label>
                                        <div class="relative">
                                            @svg($icon, 'pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-outline')
                                            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" autocomplete="{{ $auto }}" @if ($required) required @endif
                                                   class="{{ $input }} pl-10" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                                        </div>
                                        @error($name) <p id="{{ $name }}-error" class="text-sm text-error">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label for="customer_note" class="{{ $label }}">{{ __('Notas para la tienda') }} <span class="font-normal text-outline">({{ __('opcional') }})</span></label>
                                    <textarea id="customer_note" name="customer_note" rows="2" maxlength="500" class="{{ $input }}">{{ old('customer_note') }}</textarea>
                                </div>
                            </div>
                            <label class="mt-1 flex cursor-pointer items-start gap-3">
                                <input type="checkbox" name="marketing" value="1" @checked(old('marketing')) class="mt-1 size-4 accent-secondary">
                                <span class="text-xs text-on-surface-variant">{{ __('Quiero recibir promociones exclusivas y novedades de :store por correo. Puedes darte de baja cuando quieras con un clic.', ['store' => $store->store_name]) }}</span>
                            </label>
                        </section>

                        {{-- 4. Payment --}}
                        <section id="pago" class="{{ $card }} flex scroll-mt-48 flex-col gap-4" aria-labelledby="pago-titulo">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h2 id="pago-titulo" class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-lock-closed', 'size-6 text-secondary') {{ __('Método de pago seguro') }}</h2>
                                <span class="flex items-center gap-1 text-[11px] text-on-surface-variant">@svg('heroicon-o-shield-check', 'size-4 text-success') {{ __('Conexión cifrada SSL') }}</span>
                            </div>
                            <div class="flex flex-col gap-2" role="radiogroup" aria-label="{{ __('Método de pago') }}">
                                @foreach ($paymentMethods as $method)
                                    @php
                                        $available = $method->isAvailable() && ($method !== PaymentMethod::PayInStore || $settings->pay_in_store_enabled);
                                        [$title, $hint, $icon] = match ($method) {
                                            PaymentMethod::Bizum => [__('Bizum'), __('Pago instantáneo desde la app de tu banco'), 'heroicon-o-qr-code'],
                                            PaymentMethod::Card => [__('Tarjeta de crédito o débito'), 'Visa · Mastercard · Maestro', 'heroicon-o-credit-card'],
                                            PaymentMethod::PayPal => [__('PayPal'), __('Paga con tu cuenta PayPal'), 'heroicon-o-wallet'],
                                            PaymentMethod::PayInStore => [__('Pagar en la tienda al recoger'), __('Efectivo, tarjeta o Bizum en el mostrador de la tienda'), 'heroicon-o-building-storefront'],
                                        };
                                    @endphp
                                    <label @class(['flex items-center justify-between gap-3 rounded-xl p-4 ring-2 transition-all', 'cursor-pointer' => $available, 'cursor-not-allowed opacity-60' => ! $available])
                                           :class="payment === '{{ $method->value }}' ? 'bg-surface-container-low ring-secondary' : 'bg-white ring-outline-variant/40'"
                                           @if ($method === PaymentMethod::PayInStore) x-show="fulfillment === 'pickup'" @endif>
                                        <span class="flex items-center gap-3">
                                            <input type="radio" name="payment_method" value="{{ $method->value }}" x-model="payment" @disabled(! $available) class="size-4 accent-secondary">
                                            <span>
                                                <span class="flex flex-wrap items-center gap-2 text-base font-bold text-primary">
                                                    {{ $title }}
                                                    @if (! $available)
                                                        <span class="rounded-full bg-surface-container-high px-2 py-0.5 text-[11px] font-bold text-on-surface-variant">{{ __('Próximamente') }}</span>
                                                    @elseif ($method === PaymentMethod::PayInStore)
                                                        <span class="rounded-full bg-secondary-container px-2 py-0.5 text-[11px] font-bold text-secondary">{{ __('Recomendado') }}</span>
                                                    @endif
                                                </span>
                                                <span class="block text-xs text-on-surface-variant">{{ $hint }}</span>
                                            </span>
                                        </span>
                                        @svg($icon, 'size-6 shrink-0 text-secondary')
                                    </label>
                                @endforeach
                            </div>
                            @error('payment_method') <p class="text-sm text-error">{{ $message }}</p> @enderror
                        </section>
                    </form>
                </div>

                {{-- RIGHT: sticky summary --}}
                <aside class="flex flex-col gap-4 lg:sticky lg:top-40 lg:col-span-4" aria-label="{{ __('Resumen del pedido') }}">
                    <div class="flex flex-col gap-4 rounded-2xl bg-white p-4 shadow-md ring-1 ring-outline-variant/60 md:p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-[22px] font-bold tracking-tight text-primary">{{ __('Resumen del pedido') }}</h2>
                            @svg('heroicon-o-receipt-percent', 'size-6 text-secondary')
                        </div>
                        @foreach (['pickup' => $pickup, 'delivery' => $delivery] as $key => $t)
                            <div x-show="fulfillment === '{{ $key }}'" @if ($key !== $fulfillment->value) x-cloak @endif class="flex flex-col gap-2.5 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">{{ trans_choice('Subtotal (:count artículo)|Subtotal (:count artículos)', $t->itemCount) }}</span>
                                    <span class="font-semibold text-primary">{{ Money::format($t->subtotal) }}</span>
                                </div>
                                @if ($t->savings > 0)
                                    <div class="flex items-center justify-between text-success">
                                        <span class="flex items-center gap-1">@svg('heroicon-o-tag', 'size-4') {{ __('Ahorro en ofertas') }}</span>
                                        <span class="font-bold">-{{ Money::format($t->savings) }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between">
                                    <span class="text-on-surface-variant">{{ $key === 'pickup' ? __('Recogida en tienda') : __('Envío a domicilio') }}</span>
                                    <span class="font-bold {{ $t->shipping === 0 ? 'uppercase text-success' : 'text-primary' }}">{{ $t->shipping === 0 ? __('GRATIS') : Money::format($t->shipping) }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-1 text-xs text-on-surface-variant">
                                    <span>{{ __('IVA incluido') }}</span>
                                    <span>{{ Money::format($t->tax) }}</span>
                                </div>
                                <div class="my-1 h-px w-full bg-surface-container-high"></div>
                                <div class="flex items-end justify-between py-1">
                                    <div class="flex flex-col">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Total a pagar') }}</span>
                                        <span class="text-xs font-semibold text-success">{{ __('Todos los impuestos incluidos') }}</span>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="whitespace-nowrap text-3xl font-black leading-none tracking-tight text-primary xl:text-[34px]">{{ Money::format($t->total()) }}</span>
                                        @if ($t->savings > 0)
                                            <span class="mt-1 text-xs font-bold text-secondary">{{ __('Ahorras :amount hoy', ['amount' => Money::format($t->savings)]) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="submit" form="checkout-form" :disabled="!payment"
                                        class="flex min-h-[54px] w-full items-center justify-center gap-2 rounded-xl bg-secondary px-4 py-3.5 text-center text-base font-extrabold text-white shadow-lg shadow-secondary/25 transition-all hover:bg-secondary-hover active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50">
                                    @svg('heroicon-s-lock-closed', 'size-5') <span>{{ __('Confirmar pedido · :amount', ['amount' => Money::format($t->total())]) }}</span>
                                </button>
                            </div>
                        @endforeach
                        <p class="px-2 text-center text-[11px] leading-relaxed text-on-surface-variant">
                            {!! __('Al confirmar la compra aceptas las :terms y la :privacy de :store.', [
                                'terms' => '<a href="'.e(route('store.page', 'condiciones-generales')).'" class="underline hover:text-primary" target="_blank">'.e(__('condiciones de venta')).'</a>',
                                'privacy' => '<a href="'.e(route('store.page', 'politica-de-privacidad')).'" class="underline hover:text-primary" target="_blank">'.e(__('política de privacidad')).'</a>',
                                'store' => e($store->store_name),
                            ]) !!}
                        </p>
                        <ul class="flex flex-col gap-2 pt-2 text-xs">
                            <li class="flex items-center gap-2.5 rounded-lg bg-surface-container-low p-2 text-on-surface">@svg('heroicon-o-shield-check', 'size-5 shrink-0 text-secondary') <span>{!! __(':strong con certificado bancario SSL', ['strong' => '<strong>'.e(__('Pago 100% cifrado')).'</strong>']) !!}</span></li>
                            @if ($store->address_line)
                                <li class="flex items-center gap-2.5 rounded-lg bg-surface-container-low p-2 text-on-surface">@svg('heroicon-o-building-storefront', 'size-5 shrink-0 text-primary') <span><strong>{{ __('Garantía tienda física:') }}</strong> {{ $store->address_line }}</span></li>
                            @endif
                            <li class="flex items-center gap-2.5 rounded-lg bg-surface-container-low p-2 text-on-surface">@svg('heroicon-o-arrow-path', 'size-5 shrink-0 text-success') <span>{!! __(':strong garantizada sin complicaciones', ['strong' => '<strong>'.e(__('30 días de devolución')).'</strong>']) !!}</span></li>
                        </ul>
                    </div>

                    @if ($crossSell->isNotEmpty())
                        <div class="flex flex-col gap-2 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-outline-variant/60">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1.5 text-[13px] font-bold text-primary">@svg('heroicon-o-plus-circle', 'size-[18px] text-secondary') {{ __('Añade a tu pedido:') }}</span>
                                <span class="text-[11px] font-bold text-secondary">{{ __('Oferta cesta') }}</span>
                            </div>
                            @foreach ($crossSell as $suggestion)
                                @php $sv = $suggestion->variants->sortBy('price_cents')->first(); @endphp
                                <div class="flex items-center gap-2 rounded-xl bg-surface-container-low/60 p-2">
                                    <span class="flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-surface-container-high">
                                        @if ($suggestion->imageUrl('thumb'))<img src="{{ $suggestion->imageUrl('thumb') }}" alt="" class="size-full object-cover">@else @svg('heroicon-o-photo', 'size-6 text-outline') @endif
                                    </span>
                                    <div class="flex min-w-0 flex-1 flex-col">
                                        <a href="{{ route('store.product', $suggestion->slug) }}" class="truncate text-[13px] font-bold text-primary hover:text-secondary">{{ $suggestion->name }}</a>
                                        <span class="text-[15px] font-extrabold text-primary">{{ Money::format($sv?->price_cents) }}</span>
                                    </div>
                                    @if ($suggestion->variants->count() === 1 && $sv)
                                        <form method="post" action="{{ route('cart.add') }}">
                                            @csrf
                                            <input type="hidden" name="variant" value="{{ $sv->ulid }}">
                                            <button type="submit" class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-[13px] font-bold text-white transition-colors hover:bg-primary-container">+ {{ __('Añadir') }}</button>
                                        </form>
                                    @else
                                        <a href="{{ route('store.product', $suggestion->slug) }}" class="shrink-0 rounded-lg bg-primary px-3 py-1.5 text-[13px] font-bold text-white">{{ __('Ver') }}</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($whatsapp || $store->email)
                        <div class="flex flex-col gap-2 rounded-xl bg-surface-container-low p-4">
                            <p class="flex items-center gap-2 text-[15px] font-bold text-primary">@svg('heroicon-o-question-mark-circle', 'size-[22px] text-secondary') {{ __('¿Necesitas ayuda con el pedido?') }}</p>
                            <p class="text-xs text-on-surface-variant">{{ __('Nuestro equipo de la tienda te atiende en castellano o catalán:') }}</p>
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[13px] font-bold">
                                @if ($whatsapp)
                                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-primary shadow-sm hover:text-secondary">@svg('heroicon-o-chat-bubble-left-right', 'size-[18px] text-secondary') WhatsApp: {{ $store->phone }}</a>
                                @elseif ($store->email)
                                    <a href="mailto:{{ $store->email }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-primary shadow-sm hover:text-secondary">@svg('heroicon-o-envelope', 'size-[18px] text-secondary') {{ $store->email }}</a>
                                @endif
                                @if ($hours->has('h'))<span class="text-xs font-normal text-on-surface-variant">{{ $hours->text('h') }}</span>@endif
                            </div>
                        </div>
                    @endif
                </aside>
            </div>

            {{-- Bottom trust band --}}
            <section class="mt-10 grid grid-cols-1 gap-4 pt-6 md:grid-cols-3" aria-label="{{ __('Garantías') }}">
                @foreach ([
                    ['heroicon-o-building-storefront', __('Tienda física en Av. Meridiana'), __('Gran superficie de barrio de referencia desde hace más de 25 años.'), 'text-primary'],
                    ['heroicon-o-arrows-right-left', __('Cambios fáciles y rápidos'), __('Trae tu tique a la tienda o solicita la recogida a domicilio.'), 'text-secondary'],
                    ['heroicon-o-check-badge', __('Atención vecinal personalizada'), __('Llámanos o escríbenos antes de finalizar tu pedido.'), 'text-success'],
                ] as [$icon, $title, $text, $colour])
                    <div class="flex items-center gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-outline-variant/40">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-surface-container-high {{ $colour }}" aria-hidden="true">@svg($icon, 'size-7')</span>
                        <div>
                            <h3 class="text-base font-bold text-primary">{{ $title }}</h3>
                            <p class="text-xs text-on-surface-variant">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    </div>
@endsection
