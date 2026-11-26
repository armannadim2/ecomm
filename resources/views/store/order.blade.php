@extends('store.layouts.app')

{{--
    Order confirmation and status page. Design: Stitch "Confirmació de Comanda - Tot & Més Barcelona".
    Variables: $order (Order with items.product), $settings (CheckoutSettings), $isPickup (bool)
    Reached right after checkout, from "Mis pedidos", or through the signed link in the confirmation e-mail.
--}}

@php
    use App\Domain\Orders\Enums\OrderStatus;
    use App\Domain\Orders\Enums\PaymentStatus;
    use App\Support\Money;

    $hours = new \App\Domain\Content\Blocks\Block('checkout', ['h' => $settings->opening_hours, 'n' => $settings->pickup_note]);
    $maps = \App\Domain\Content\Blocks\Block::resolveUrl('[mapa]');
    $whatsapp = \App\Domain\Content\Blocks\Block::resolveUrl('[whatsapp]');
    $steps = $isPickup
        ? [[OrderStatus::Confirmed, __('Confirmado'), 'heroicon-o-check'], [OrderStatus::Preparing, __('En preparación'), 'heroicon-o-archive-box'], [OrderStatus::ReadyForPickup, __('Listo'), 'heroicon-o-hand-raised'], [OrderStatus::Completed, __('Entregado'), 'heroicon-o-shopping-bag']]
        : [[OrderStatus::Confirmed, __('Confirmado'), 'heroicon-o-check'], [OrderStatus::Preparing, __('En preparación'), 'heroicon-o-archive-box'], [OrderStatus::Shipped, __('Enviado'), 'heroicon-o-truck'], [OrderStatus::Completed, __('Entregado'), 'heroicon-o-home']];
    $order_ = array_map(fn ($s) => $s[0], $steps);
    $currentIndex = $order->status === OrderStatus::PendingPayment ? -1 : array_search($order->status, $order_, true);
    $cancelled = $order->status === OrderStatus::Cancelled;
    $awaitingPayment = $order->isAwaitingOnlinePayment();
    $paymentOpen = $awaitingPayment && ! $order->payment_expires_at?->isPast();
    $checking = $awaitingPayment && session('payment_returned');
    $placed = $order->placed_at->timezone('Europe/Madrid')->locale(app()->getLocale());
@endphp

@section('title', __('Pedido :number', ['number' => $order->number]))
@section('fullBleed', true)

@push('head')
    <meta name="robots" content="noindex">
    {{-- Back from the bank: the notification usually arrives within seconds. --}}
    @if ($checking)
        <meta http-equiv="refresh" content="5">
    @endif
@endpush

@section('content')
    {{-- Status banner --}}
    <section class="w-full bg-surface-container-low px-4 py-10 md:px-12">
        <div class="mx-auto flex max-w-[1320px] flex-col justify-between gap-6 md:flex-row md:items-center">
            <div class="flex items-start gap-4 sm:items-center">
                <span @class(['flex size-14 shrink-0 items-center justify-center rounded-full shadow-sm', 'bg-success-container text-on-success-fixed-variant' => ! $cancelled && ! $awaitingPayment, 'bg-error-container text-error' => $cancelled, 'bg-secondary-container text-secondary' => $awaitingPayment]) aria-hidden="true">
                    @svg($cancelled ? 'heroicon-s-x-circle' : ($awaitingPayment ? 'heroicon-s-clock' : 'heroicon-s-check-circle'), 'size-8')
                </span>
                <div class="flex flex-col">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <span @class(['rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase text-white', 'bg-success' => ! $cancelled && ! $awaitingPayment, 'bg-secondary' => $awaitingPayment, 'bg-error' => $cancelled])>{{ __($order->status->getLabel()) }}</span>
                        <span class="text-xs font-medium text-on-surface-variant">{{ __('Ref.') }} #{{ $order->number }} · {{ __('Realizado el :date', ['date' => $placed->isoFormat('LLL')]) }}</span>
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-primary sm:text-[32px] sm:leading-10">
                        @if ($cancelled)
                            {{ __('Pedido cancelado') }}
                        @elseif ($checking)
                            {{ __('Estamos confirmando tu pago…') }}
                        @elseif ($awaitingPayment)
                            {{ __('Falta completar el pago') }}
                        @else
                            {{ __('¡Pedido confirmado! Gracias, :name', ['name' => $order->first_name]) }}
                        @endif
                    </h1>
                    @if ($cancelled && $order->cancel_reason === \App\Domain\Orders\Actions\ChangeOrderStatus::CANCEL_PAYMENT_EXPIRED)
                        <p class="mt-1 text-sm text-on-surface-variant">{{ __('No recibimos el pago a tiempo y los productos han vuelto a la venta. No se ha hecho ningún cargo.') }}</p>
                    @elseif ($checking)
                        <p class="mt-1 text-sm text-on-surface-variant">{{ __('El banco nos avisa en unos segundos. Esta página se actualiza sola.') }}</p>
                    @elseif ($awaitingPayment)
                        <p class="mt-1 text-sm text-on-surface-variant">
                            @if ($paymentOpen)
                                {{ __('Guardamos tus productos hasta las :time. Paga para confirmar el pedido.', ['time' => $order->payment_expires_at->timezone('Europe/Madrid')->format('H:i')]) }}
                            @else
                                {{ __('El tiempo para pagar ha terminado. Si ya has pagado, lo confirmaremos en unos minutos.') }}
                            @endif
                        </p>
                    @endif
                    @unless ($cancelled || $awaitingPayment)
                        <p class="mt-1 text-sm text-on-surface-variant">
                            {!! __('Hemos enviado el resumen a :email y te avisaremos cuando :when.', [
                                'email' => '<span class="font-bold text-on-surface">'.e($order->email).'</span>',
                                'when' => e($isPickup ? __('esté listo en el mostrador') : __('salga hacia tu dirección')),
                            ]) !!}
                        </p>
                    @endunless
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if ($paymentOpen && ! $checking)
                    <a href="{{ route('payment.start', $order) }}" class="flex items-center gap-2 rounded-xl bg-secondary px-5 py-3 text-sm font-extrabold text-white shadow-cta transition-colors hover:bg-secondary-hover print:hidden">
                        @svg('heroicon-s-lock-closed', 'size-5') <span>{{ __('Pagar ahora · :amount', ['amount' => Money::format($order->total_cents)]) }}</span>
                    </a>
                @endif
                @foreach ($order->invoices as $invoice)
                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('invoice.download', now()->addHour(), $invoice) }}" class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-[13px] font-semibold text-primary shadow-sm transition-colors hover:bg-surface-container-high print:hidden">
                        @svg('heroicon-o-document-arrow-down', 'size-[18px]') <span>{{ $invoice->isCorrective() ? __('Factura rectificativa :n', ['n' => $invoice->code]) : __('Factura :n', ['n' => $invoice->code]) }}</span>
                    </a>
                @endforeach
                <button type="button" onclick="window.print()" class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-[13px] font-semibold text-primary shadow-sm transition-colors hover:bg-surface-container-high print:hidden">
                    @svg('heroicon-o-printer', 'size-[18px]') <span>{{ __('Imprimir tique / PDF') }}</span>
                </button>
                @if ($whatsapp)
                    <a href="{{ $whatsapp }}?text={{ rawurlencode(__('Hola, quiero consultar el pedido :number', ['number' => $order->number])) }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm transition-colors hover:bg-primary-container print:hidden">
                        @svg('heroicon-o-chat-bubble-left-right', 'size-[18px]') <span>{{ __('Consultar por WhatsApp') }}</span>
                    </a>
                @endif
            </div>
        </div>
    </section>

    @if (session('payment_error') && ! $order->payment_status->isPaid())
        <div class="mx-auto mt-6 max-w-[1320px] px-4 md:px-12" role="alert">
            <p class="flex items-center gap-2 rounded-xl bg-error-container px-4 py-3 text-sm font-semibold text-error">@svg('heroicon-o-exclamation-triangle', 'size-5 shrink-0') {{ session('payment_error') }}</p>
        </div>
    @endif

    @if ($order->tracking_number)
        <div class="mx-auto mt-6 max-w-[1320px] px-4 md:px-12">
            <p class="flex flex-wrap items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm text-on-surface shadow-sm ring-1 ring-outline-variant/60">
                @svg('heroicon-o-truck', 'size-5 shrink-0 text-secondary')
                <span>{{ __('Seguimiento:') }} <strong>{{ $order->carrier }}</strong> {{ $order->tracking_number }}</span>
                @if ($order->tracking_url)
                    <a href="{{ $order->tracking_url }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-secondary underline-offset-2 hover:underline">{{ __('Ver el envío') }}</a>
                @endif
            </p>
        </div>
    @endif

    <section class="w-full px-4 py-10 md:px-12">
        <div class="mx-auto grid max-w-[1320px] grid-cols-1 items-start gap-6 lg:grid-cols-12">
            {{-- LEFT --}}
            <div class="flex flex-col gap-6 lg:col-span-8">
                {{-- Fulfilment card with timeline --}}
                <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-outline-variant/60">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                            @svg($isPickup ? 'heroicon-o-building-storefront' : 'heroicon-o-truck', 'size-6 text-secondary')
                            {{ $isPickup ? __('Recogida exprés en tienda') : __('Envío a domicilio') }}
                        </h2>
                        @unless ($cancelled)
                            <span class="rounded-full bg-secondary-container px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-secondary">{{ __('Preparación prioritaria') }}</span>
                        @endunless
                    </div>

                    @unless ($cancelled)
                        <ol class="grid grid-cols-4 gap-2 py-2" aria-label="{{ __('Estado del pedido') }}">
                            @foreach ($steps as $i => [$status, $label, $icon])
                                @php $done = $currentIndex !== false && $i < $currentIndex; $current = $currentIndex === $i; @endphp
                                <li @class(['flex flex-col items-center gap-1.5 text-center', 'opacity-60' => ! $done && ! $current]) @if ($current) aria-current="step" @endif>
                                    <span @class([
                                        'flex size-8 items-center justify-center rounded-full',
                                        'bg-success-container text-on-success-fixed-variant' => $done,
                                        'bg-secondary text-white shadow-sm' => $current,
                                        'bg-surface-container-highest text-on-surface-variant' => ! $done && ! $current,
                                    ])>@svg($done ? 'heroicon-o-check' : $icon, 'size-[18px]')</span>
                                    <span @class(['text-[11px] font-bold', 'text-secondary' => $current, 'text-primary' => $done, 'text-on-surface-variant' => ! $done && ! $current])>{{ $i + 1 }}. {{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endunless

                    @if ($isPickup)
                        @if ($hours->has('n') && ! $cancelled)
                            <div class="flex items-center gap-2 rounded-xl bg-surface-container-low p-4 text-primary">
                                @svg('heroicon-o-clock', 'size-6 shrink-0 text-secondary')
                                <span class="text-[13px] font-bold">{{ __('Hora estimada de recogida:') }} <span class="text-base text-secondary">{{ $hours->text('n') }}</span></span>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-4 pt-1 md:grid-cols-2">
                            <div class="flex flex-col gap-1.5 rounded-xl bg-surface-container p-4">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Dirección del mostrador') }}</span>
                                <p class="text-lg font-bold text-primary">{{ $store->store_name }}</p>
                                @if ($store->address_line)
                                    <p class="text-sm text-on-surface">{{ $store->address_line }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ trim($store->postal_code.' '.$store->city) }}</p>
                                @endif
                            </div>
                            <div class="flex flex-col justify-between gap-2 rounded-xl bg-surface-container p-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Horario y mostrador') }}</span>
                                    @if ($hours->has('h'))<p class="text-sm font-bold text-primary">{{ $hours->text('h') }}</p>@endif
                                    <p class="flex items-center gap-1.5 text-xs font-bold text-success">@svg('heroicon-o-user-group', 'size-4') {{ __('Mostrador Click & Collect sin colas') }}</p>
                                </div>
                                @if ($maps)
                                    <a href="{{ $maps }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 self-start rounded-lg bg-white px-3 py-2 text-[13px] font-semibold text-primary shadow-sm hover:bg-surface-container-high print:hidden">
                                        @svg('heroicon-o-map-pin', 'size-4 text-secondary') {{ __('Cómo llegar (Google Maps)') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl bg-surface-container p-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Dirección de entrega') }}</span>
                            <address class="mt-1 text-sm not-italic text-primary">
                                <strong>{{ $order->fullName() }}</strong><br>
                                @foreach ($order->shippingLines() as $line){{ $line }}<br>@endforeach
                            </address>
                        </div>
                    @endif
                </div>

                {{-- Pickup code with a real, scannable QR --}}
                @if ($isPickup && $order->pickup_code && ! $cancelled)
                    <div class="flex flex-col items-center justify-between gap-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-outline-variant/60 md:flex-row">
                        <div class="flex max-w-sm flex-col gap-2">
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-primary px-2.5 py-0.5 text-[11px] font-bold uppercase text-white">{{ __('Pase digital') }}</span>
                                <span class="text-[11px] font-bold text-secondary">{{ __('Enséñalo en caja') }}</span>
                            </div>
                            <h3 class="text-[22px] font-bold leading-tight text-primary">{{ __('Código de recogida exprés') }}</h3>
                            <p class="text-sm text-on-surface-variant">{{ __('Muestra este código QR desde tu móvil o di tu nombre y el código en el mostrador para recoger tu pedido al momento.') }}</p>
                            <div class="mt-2 flex flex-col pt-2">
                                <span class="text-xs text-on-surface-variant">{{ __('Titular:') }}</span>
                                <span class="text-[15px] font-bold text-primary">{{ $order->fullName() }}</span>
                                <span class="text-xs text-on-surface-variant">{{ $order->phone }}</span>
                            </div>
                        </div>
                        <div class="flex w-full shrink-0 flex-col items-center rounded-xl bg-surface-container-low p-4 text-center shadow-inner sm:w-64"
                             x-data="{ copied: false }">
                            <div class="rounded-lg bg-white p-3 shadow-sm [&_svg]:size-40" role="img" aria-label="{{ __('Código QR de recogida :code', ['code' => $order->pickup_code]) }}">
                                {!! $order->pickupQrSvg() !!}
                            </div>
                            <span class="mt-3 text-[11px] uppercase tracking-wider text-on-surface-variant">{{ __('Código de mostrador') }}</span>
                            <span class="mt-0.5 text-lg font-bold tracking-widest text-secondary">{{ $order->pickup_code }}</span>
                            <button type="button" class="mt-2 flex items-center gap-1 text-[11px] font-semibold text-primary hover:text-secondary print:hidden"
                                    x-on:click="navigator.clipboard?.writeText(@js($order->pickup_code)); copied = true; setTimeout(() => copied = false, 2000)">
                                @svg('heroicon-o-clipboard-document', 'size-4')
                                <span x-text="copied ? @js(__('¡Copiado!')) : @js(__('Copiar código'))">{{ __('Copiar código') }}</span>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Items --}}
                <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-outline-variant/60">
                    <h3 class="text-lg font-bold text-primary">{{ __('Artículos del pedido (:count)', ['count' => $order->itemCount()]) }}</h3>
                    @foreach ($order->items as $item)
                        @php $image = $item->product?->imageUrl('thumb'); $cat = $item->product?->categories->first()?->name; @endphp
                        <div class="flex flex-col justify-between gap-4 rounded-xl bg-surface-container-low p-4 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-4">
                                <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white p-1 shadow-sm">
                                    @if ($image)<img src="{{ $image }}" alt="" class="size-full rounded-md object-cover">@else @svg('heroicon-o-photo', 'size-7 text-outline') @endif
                                </span>
                                <div class="flex flex-col">
                                    @if ($cat)<span class="text-[11px] font-bold uppercase text-secondary">{{ $cat }}</span>@endif
                                    <p class="text-base font-bold text-primary">{{ $item->product_name }}</p>
                                    <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-on-surface-variant">
                                        @if ($item->variant_name)<span>{{ $item->variant_name }}</span><span aria-hidden="true">•</span>@endif
                                        <span>{{ __('Ref.') }} {{ $item->sku }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center justify-between sm:flex-col sm:items-end sm:justify-center">
                                <span class="text-sm font-medium text-on-surface-variant">{{ __('Cantidad: :qty', ['qty' => $item->quantity]) }}</span>
                                <span class="text-lg font-bold text-primary">{{ Money::format($item->line_total_cents) }}</span>
                            </div>
                        </div>
                    @endforeach
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 text-xs">
                        <span class="flex items-center gap-2 text-primary">
                            <span class="flex size-7 items-center justify-center rounded-full bg-secondary-container text-secondary">@svg('heroicon-o-credit-card', 'size-4')</span>
                            {{ __('Forma de pago:') }} <strong>{{ __($order->payment_method->getLabel()) }}</strong>
                        </span>
                        <span @class(['flex items-center gap-1.5 font-bold', 'text-success' => $order->payment_status === PaymentStatus::Paid, 'text-on-surface-variant' => $order->payment_status !== PaymentStatus::Paid])>
                            @svg('heroicon-o-check-badge', 'size-4')
                            {{ $order->payment_status === PaymentStatus::Paid ? __('Pagado') : ($isPickup ? __('Pagarás al recoger') : __('Pago pendiente')) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- RIGHT --}}
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-outline-variant/60">
                    <h3 class="text-lg font-bold text-primary">{{ __('Resumen económico') }}</h3>
                    <div class="flex flex-col gap-2 pt-1 text-sm">
                        <div class="flex items-center justify-between text-on-surface-variant">
                            <span>{{ trans_choice('Subtotal (:count artículo)|Subtotal (:count artículos)', $order->itemCount()) }}</span>
                            <span class="font-semibold text-primary">{{ Money::format($order->subtotal_cents) }}</span>
                        </div>
                        @if ($order->savings_cents > 0)
                            <div class="flex items-center justify-between text-success">
                                <span class="flex items-center gap-1">@svg('heroicon-o-tag', 'size-4') {{ __('Ahorro en ofertas') }}</span>
                                <span class="font-bold">-{{ Money::format($order->savings_cents) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-on-surface-variant">
                            <span>{{ $isPickup ? __('Recogida en tienda') : __('Envío a domicilio') }}</span>
                            <span class="font-bold {{ $order->shipping_cents === 0 ? 'uppercase text-success' : 'text-primary' }}">{{ $order->shipping_cents === 0 ? __('GRATIS') : Money::format($order->shipping_cents) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-on-surface-variant">
                            <span>{{ __('Base imponible') }}</span>
                            <span>{{ Money::format($order->total_cents - $order->tax_cents) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[13px] text-on-surface-variant">
                            <span>{{ __('IVA') }}</span>
                            <span>{{ Money::format($order->tax_cents) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-surface-container-low p-4">
                        <div class="flex flex-col">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Total') }}</span>
                            <span class="text-xs font-semibold text-success">{{ __('IVA incluido') }}</span>
                        </div>
                        <span class="text-[32px] font-bold leading-10 text-primary">{{ Money::format($order->total_cents) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-outline-variant/60">
                    <h4 class="flex items-center gap-2 text-lg font-bold text-primary">@svg('heroicon-o-check-badge', 'size-6 text-secondary') {{ __('Servicio de barrio garantizado') }}</h4>
                    <div class="flex flex-col gap-2 text-xs text-on-surface-variant">
                        <p class="flex items-start gap-2.5">@svg('heroicon-o-arrow-path', 'mt-0.5 size-5 shrink-0 text-success') <span>{!! __(':strong si cambias de idea, devuelve el producto en la tienda física con tu tique en el móvil.', ['strong' => '<strong class="font-semibold text-primary">'.e(__('30 días de devolución:')).'</strong>']) !!}</span></p>
                        <p class="flex items-start gap-2.5">@svg('heroicon-o-face-smile', 'mt-0.5 size-5 shrink-0 text-secondary') <span>{!! __(':strong puedes abrir y comprobar el paquete con nuestro equipo antes de irte.', ['strong' => '<strong class="font-semibold text-primary">'.e(__('Revisión en el mostrador:')).'</strong>']) !!}</span></p>
                    </div>
                    @if ($store->phone)
                        <div class="flex items-center justify-between gap-2 rounded-xl bg-surface-container p-4">
                            <div class="flex flex-col">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ __('¿Preguntas o cambios?') }}</span>
                                <span class="text-[13px] font-bold text-primary">{{ $store->phone }}</span>
                            </div>
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->phone) }}" class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary shadow-sm hover:text-secondary" aria-label="{{ __('Llamar a la tienda') }}">@svg('heroicon-o-phone', 'size-5')</a>
                        </div>
                    @endif
                </div>

                <a href="{{ route('store.home') }}" class="w-full rounded-xl bg-primary py-3 text-center text-[13px] font-bold text-white shadow-sm transition-colors hover:bg-primary-container print:hidden">{{ __('Volver a la tienda online') }}</a>
            </div>
        </div>
    </section>
@endsection
