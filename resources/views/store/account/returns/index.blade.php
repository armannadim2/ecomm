@extends('store.account.layout')

{{-- Stitch "Gestió de Devolucions i Canvis": list of requests + conditions. Variables: $requests, $canRequest --}}

@section('title', __('Devoluciones y cambios'))

@php
    use App\Support\Money;
    $date = fn ($d) => $d?->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LL');
@endphp

@section('account')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ __('Devoluciones y cambios') }}</h1>
            <p class="mt-1 text-sm text-on-surface-variant">{{ __('Pide la devolución o el cambio de un pedido entregado y sigue aquí cómo va.') }}</p>
        </div>
        @if ($canRequest)
            <a href="{{ route('account.returns.create') }}" class="rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">{{ __('Nueva solicitud') }}</a>
        @endif
    </div>

    <div class="mt-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Mis solicitudes') }}</h2>
        @forelse ($requests as $request)
            <a href="{{ route('account.returns.show', $request) }}" class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-outline-variant/60 bg-surface-container-low p-4 transition-colors hover:border-secondary">
                <span class="min-w-0">
                    <span class="block text-sm font-bold text-primary">{{ $request->number }} · {{ __($request->type->getLabel()) }}</span>
                    <span class="block text-xs text-on-surface-variant">
                        {{ __('Pedido') }} {{ $request->order->number }} · {{ $date($request->requested_at) }} · {{ trans_choice(':count artículo|:count artículos', $request->itemCount()) }}
                    </span>
                </span>
                <span class="flex items-center gap-3">
                    <span class="text-sm font-bold text-primary">{{ Money::format($request->refund_cents) }}</span>
                    @include('store.account.returns.partials.status-badge', ['status' => $request->status])
                </span>
            </a>
        @empty
            <p class="mt-3 text-sm text-on-surface-variant">
                {{ $canRequest ? __('Todavía no has pedido ninguna devolución ni cambio.') : __('Cuando recibas un pedido podrás pedir aquí su devolución o cambio.') }}
            </p>
        @endforelse
    </div>

    <div class="mt-8">
        @include('store.account.returns.partials.conditions')
    </div>
@endsection
