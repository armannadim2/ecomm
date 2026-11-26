@extends('store.account.layout')

{{--
    Account home (Stitch "El Meu Compte", D:\Projects\eComm\design\totmes-el-meu-compte.html), real data only.
    Variables: $customer, $activeOrder (?Order with items), $orders (other orders), $address (?Address), $openingHours (?string)
--}}

@section('title', __('Mi cuenta'))

@php
    use App\Support\Money;
    $date = fn ($d) => $d?->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LL');
    $mapUrl = \App\Domain\Content\Blocks\Block::resolveUrl('[mapa]');
@endphp

@section('accountRaw')
    {{-- Greeting --}}
    <section class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm sm:p-8">
        <div class="pointer-events-none absolute -right-10 -top-10 size-40 rounded-full bg-secondary-container blur-2xl" aria-hidden="true"></div>
        <div class="relative">
            <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Hola, :name', ['name' => $customer->first_name]) }} 👋</h1>
            <p class="mt-1 text-sm text-on-surface-variant">{{ __('Cliente desde :date', ['date' => $customer->created_at->locale(app()->getLocale())->isoFormat('MMMM YYYY')]) }}</p>
            @if ($store->address_line)
                <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-surface-container-low px-3 py-1.5 text-xs font-semibold text-primary">
                    @svg('heroicon-o-building-storefront', 'size-4 text-accent') {{ __('Tu tienda: :address', ['address' => $store->fullAddress()]) }}
                </p>
            @endif
        </div>
    </section>

    {{-- Order in progress / ready at the counter --}}
    @if ($activeOrder)
        @php
            $ready = $activeOrder->status === \App\Domain\Orders\Enums\OrderStatus::ReadyForPickup;
            $isPickup = $activeOrder->fulfillment === \App\Domain\Orders\Enums\Fulfillment::Pickup;
            $units = $activeOrder->items->sum('quantity');
        @endphp
        <section class="overflow-hidden rounded-2xl bg-white shadow-sm" aria-labelledby="active-order-title">
            <div @class(['flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-white', 'bg-success' => $ready, 'bg-primary' => ! $ready])>
                <h2 id="active-order-title" class="flex items-center gap-2 text-sm font-bold">
                    @svg($ready ? 'heroicon-o-check-circle' : 'heroicon-o-clock', 'size-5')
                    {{ $ready ? __('Pedido listo para recoger') : __('Pedido en curso') }}
                </h2>
                <span class="text-xs font-semibold text-white/90">{{ __('Ref.') }} {{ $activeOrder->number }}</span>
            </div>
            <div class="grid gap-6 p-5 md:grid-cols-[1fr_auto]">
                <div>
                    <p class="text-sm font-bold text-primary">{{ __($activeOrder->status->getLabel()) }} · {{ __($activeOrder->fulfillment->getLabel()) }}</p>
                    <p class="text-xs text-on-surface-variant">{{ $date($activeOrder->placed_at) }}@if ($isPickup && $store->address_line) · {{ $store->address_line }}@endif</p>

                    <h3 class="mt-4 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ trans_choice(':count artículo|:count artículos', $units) }}</h3>
                    <ul class="mt-2 divide-y divide-outline-variant/60">
                        @foreach ($activeOrder->items->take(4) as $item)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-primary">{{ $item->quantity }} × {{ $item->product_name }}</span>
                                    @if ($item->variant_name)<span class="block text-xs text-on-surface-variant">{{ $item->variant_name }}</span>@endif
                                </span>
                                <span class="shrink-0 font-bold text-primary">{{ Money::format($item->line_total_cents) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($activeOrder->items->count() > 4)
                        <p class="text-xs text-on-surface-variant">{{ __('y :count más', ['count' => $activeOrder->items->count() - 4]) }}</p>
                    @endif
                    <p class="mt-3 flex items-baseline justify-between border-t border-outline-variant/60 pt-3">
                        <span class="text-sm text-on-surface-variant">{{ __('Total (IVA incluido)') }}</span>
                        <span class="text-xl font-extrabold text-primary">{{ Money::format($activeOrder->total_cents) }}</span>
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('order.show', $activeOrder) }}" class="inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-2.5 text-sm font-bold text-white hover:bg-secondary-hover">
                            @svg('heroicon-o-eye', 'size-4') {{ __('Ver pedido') }}
                        </a>
                        @if ($isPickup && $mapUrl)
                            <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-surface-container px-4 py-2.5 text-sm font-bold text-primary hover:bg-surface-container-high">
                                @svg('heroicon-o-map', 'size-4') {{ __('Cómo llegar') }}<span class="sr-only">{{ __('(se abre en una pestaña nueva)') }}</span>
                            </a>
                        @endif
                    </div>
                </div>

                @if ($isPickup && $activeOrder->pickup_code && $activeOrder->status !== \App\Domain\Orders\Enums\OrderStatus::Cancelled)
                    <div class="flex flex-col items-center gap-2 rounded-2xl bg-surface-container-low p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Código de recogida') }}</p>
                        <div class="rounded-lg bg-white p-2 shadow-sm [&_svg]:size-32" role="img" aria-label="{{ __('Código QR de recogida :code', ['code' => $activeOrder->pickup_code]) }}">
                            {!! $activeOrder->pickupQrSvg() !!}
                        </div>
                        <span class="text-lg font-bold tracking-widest text-secondary">{{ $activeOrder->pickup_code }}</span>
                        <p class="max-w-[12rem] text-xs text-on-surface-variant">{{ __('Muéstralo en el mostrador para recoger tu pedido.') }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Order history --}}
    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6" aria-labelledby="orders-title">
        <h2 id="orders-title" class="text-lg font-extrabold text-primary">{{ $activeOrder ? __('Pedidos anteriores') : __('Mis pedidos') }}</h2>
        @forelse ($orders as $order)
            @if ($loop->first)<ul class="mt-4 flex flex-col gap-3">@endif
            <li>
                <a href="{{ route('order.show', $order) }}" class="flex flex-col gap-3 rounded-xl bg-surface-container-low p-4 transition-colors hover:bg-surface-container sm:flex-row sm:items-center sm:justify-between">
                    <span class="flex min-w-0 items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-primary" aria-hidden="true">
                            @svg($order->status->isOpen() ? 'heroicon-o-clock' : 'heroicon-o-check', 'size-5')
                        </span>
                        <span class="min-w-0">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-primary">{{ $order->number }}</span>
                                <span class="rounded-full bg-secondary-container px-2.5 py-0.5 text-[11px] font-bold text-secondary">{{ __($order->status->getLabel()) }}</span>
                            </span>
                            <span class="block text-xs text-on-surface-variant">{{ $date($order->placed_at) }} · {{ __($order->fulfillment->getLabel()) }} · {{ trans_choice(':count artículo|:count artículos', $order->items->sum('quantity')) }}</span>
                            <span class="block truncate text-xs text-on-surface-variant">{{ $order->items->pluck('product_name')->take(3)->implode(' · ') }}</span>
                        </span>
                    </span>
                    <span class="flex items-center gap-2 self-end sm:self-center">
                        <span class="text-base font-extrabold text-primary">{{ Money::format($order->total_cents) }}</span>
                        @svg('heroicon-o-chevron-right', 'size-4 text-outline')
                    </span>
                </a>
                <div class="mt-2 flex flex-wrap items-center gap-2 px-1">
                    <form method="post" action="{{ route('account.reorder', $order) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-primary ring-1 ring-outline-variant hover:bg-surface-container-low">
                            @svg('heroicon-o-arrow-path', 'size-4') {{ __('Volver a comprar') }}
                        </button>
                    </form>
                    @foreach ($order->invoices as $invoice)
                        <a href="{{ route('invoice.download', $invoice) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-primary ring-1 ring-outline-variant hover:bg-surface-container-low">
                            @svg('heroicon-o-document-arrow-down', 'size-4') {{ $invoice->isCorrective() ? __('Factura rectificativa :n', ['n' => $invoice->code]) : __('Factura :n', ['n' => $invoice->code]) }}
                        </a>
                    @endforeach
                </div>
            </li>
            @if ($loop->last)</ul>@endif
        @empty
            <p class="mt-2 text-sm text-on-surface-variant">
                @if ($activeOrder)
                    {{ __('Aquí verás tus pedidos anteriores.') }}
                @else
                    {{ __('Desde aquí puedes gestionar tus datos y direcciones. Tus pedidos aparecerán aquí cuando hagas tu primera compra.') }}
                @endif
            </p>
            @unless ($activeOrder)
                <a href="{{ route('store.categories') }}" class="mt-4 inline-flex items-center gap-2 rounded-full bg-secondary px-4 py-2.5 text-sm font-bold text-white hover:bg-secondary-hover">{{ __('Empezar a comprar') }} @svg('heroicon-o-arrow-right', 'size-4')</a>
            @endunless
        @endforelse
    </section>

    {{-- Pickup point and delivery address --}}
    <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6" aria-labelledby="places-title">
        <div class="flex items-center justify-between gap-2">
            <h2 id="places-title" class="text-lg font-extrabold text-primary">{{ __('Recogida y direcciones') }}</h2>
            <a href="{{ route('account.addresses') }}" class="text-sm font-bold text-secondary hover:underline">{{ __('Gestionar') }}</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            @if ($store->address_line)
                <div class="flex gap-3 rounded-xl bg-surface-container-low p-4">
                    @svg('heroicon-o-building-storefront', 'size-6 shrink-0 text-accent')
                    <div class="text-sm">
                        <p class="font-bold text-primary">{{ __('Tienda :name', ['name' => $store->store_name]) }}</p>
                        <p class="text-on-surface-variant">{{ $store->fullAddress() }}</p>
                        @if ($openingHours)<p class="mt-1 text-xs text-on-surface-variant">{{ $openingHours }}</p>@endif
                        <p class="mt-1 text-xs font-bold text-success">{{ __('Recogida gratis') }}</p>
                    </div>
                </div>
            @endif
            <div class="flex gap-3 rounded-xl bg-surface-container-low p-4">
                @svg('heroicon-o-home', 'size-6 shrink-0 text-primary')
                <div class="text-sm">
                    @if ($address)
                        <p class="font-bold text-primary">{{ __('Dirección de envío') }}</p>
                        @foreach ($address->lines() as $line)
                            <p class="text-on-surface-variant">{{ $line }}</p>
                        @endforeach
                    @else
                        <p class="font-bold text-primary">{{ __('Sin dirección de envío') }}</p>
                        <a href="{{ route('account.addresses.create') }}" class="mt-1 inline-block font-bold text-secondary hover:underline">{{ __('Añadir dirección') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
