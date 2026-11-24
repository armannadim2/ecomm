@extends('store.account.layout')

{{-- Variables: $customer --}}

@section('title', __('Mi cuenta'))

@php $orders = $customer->orders()->withCount('items')->limit(20)->get(); @endphp

@section('account')
    <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Hola, :name', ['name' => $customer->first_name]) }}</h1>

    <h2 class="mt-8 text-lg font-extrabold text-primary">{{ __('Mis pedidos') }}</h2>
    @forelse ($orders as $order)
        @if ($loop->first)<ul class="mt-3 divide-y divide-outline-variant/60 rounded-2xl border border-outline-variant/60">@endif
        <li>
            <a href="{{ route('order.show', $order) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition-colors hover:bg-surface-container-low">
                <span class="flex flex-col">
                    <span class="text-sm font-bold text-primary">{{ $order->number }}</span>
                    <span class="text-xs text-on-surface-variant">{{ $order->placed_at->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LL') }} · {{ __($order->fulfillment->getLabel()) }}</span>
                </span>
                <span class="flex items-center gap-3">
                    <span class="rounded-full bg-secondary-container px-2.5 py-0.5 text-[11px] font-bold text-secondary">{{ __($order->status->getLabel()) }}</span>
                    <span class="text-sm font-extrabold text-primary">{{ \App\Support\Money::format($order->total_cents) }}</span>
                    @svg('heroicon-o-chevron-right', 'size-4 text-outline')
                </span>
            </a>
        </li>
        @if ($loop->last)</ul>@endif
    @empty
        <p class="mt-2 text-sm text-on-surface-variant">{{ __('Desde aquí puedes gestionar tus datos y direcciones. Tus pedidos aparecerán aquí cuando hagas tu primera compra.') }}</p>
    @endforelse
@endsection
