@extends('store.account.layout')

{{-- One return/exchange request with its progress. Variables: $returnRequest (with order, items) --}}

@section('title', $returnRequest->number)

@php
    use App\Domain\Orders\Enums\ReturnStatus;
    use App\Domain\Orders\Enums\ReturnType;
    use App\Support\Money;
    $date = fn ($d) => $d?->timezone('Europe/Madrid')->locale(app()->getLocale())->isoFormat('LL');
    $isExchange = $returnRequest->type === ReturnType::Exchange;
    $status = $returnRequest->status;
    $rejected = $status === ReturnStatus::Rejected;
    $steps = [
        [__('Solicitada'), $returnRequest->requested_at],
        [__('Aprobada'), $returnRequest->approved_at],
        [__('Producto recibido'), $returnRequest->received_at],
        [$isExchange ? __('Cambio entregado') : __('Reembolsada'), $returnRequest->resolved_at],
    ];
@endphp

@section('account')
    <a href="{{ route('account.returns') }}" class="text-sm font-semibold text-secondary underline-offset-2 hover:underline">← {{ __('Devoluciones y cambios') }}</a>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-primary sm:text-3xl">{{ $returnRequest->number }}</h1>
            <p class="mt-1 text-sm text-on-surface-variant">{{ __($returnRequest->type->getLabel()) }} · {{ __('Pedido') }} {{ $returnRequest->order->number }} · {{ $date($returnRequest->requested_at) }}</p>
        </div>
        @include('store.account.returns.partials.status-badge', ['status' => $status])
    </div>

    @if ($rejected)
        <p class="mt-5 rounded-2xl bg-error-container px-4 py-3 text-sm text-on-error-container">
            <strong>{{ __('No hemos podido aceptar tu solicitud.') }}</strong> {{ $returnRequest->rejection_reason }}
        </p>
    @else
        <ol class="mt-6 grid gap-3 sm:grid-cols-4" aria-label="{{ __('Progreso de la solicitud') }}">
            @foreach ($steps as [$label, $at])
                <li @class(['rounded-2xl p-3 text-sm', 'bg-success-container/50' => $at, 'bg-surface-container-low' => ! $at])>
                    <span class="flex items-center gap-2 font-bold {{ $at ? 'text-on-success-fixed-variant' : 'text-on-surface-variant' }}">
                        @svg($at ? 'heroicon-o-check-circle' : 'heroicon-o-clock', 'size-5') {{ $label }}
                    </span>
                    <span class="mt-1 block text-xs text-on-surface-variant">{{ $at ? $date($at) : __('Pendiente') }}</span>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($status === ReturnStatus::Approved)
        <p class="mt-5 rounded-2xl bg-surface-container-low px-4 py-3 text-sm text-primary">
            <strong>{{ __('Ya puedes traer el producto a la tienda.') }}</strong>
            @if ($store->address_line) {{ $store->fullAddress() }}.@endif {{ __('Enseña el número de solicitud en el mostrador.') }}
        </p>
    @endif

    <h2 class="mt-8 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Artículos') }}</h2>
    <ul class="mt-2 divide-y divide-outline-variant/60">
        @foreach ($returnRequest->items as $item)
            <li class="flex items-center justify-between gap-3 py-3 text-sm">
                <span class="min-w-0">
                    <span class="block font-semibold text-primary">{{ $item->quantity }} × {{ $item->product_name }}</span>
                    @if ($item->variant_name)<span class="block text-xs text-on-surface-variant">{{ $item->variant_name }}</span>@endif
                </span>
                <span class="shrink-0 font-bold text-primary">{{ Money::format($item->totalCents()) }}</span>
            </li>
        @endforeach
        <li class="flex items-center justify-between gap-3 py-3 text-sm">
            <span class="text-on-surface-variant">{{ $isExchange ? __('Valor de los artículos a cambiar') : __('Importe a reembolsar') }}</span>
            <span class="text-lg font-extrabold text-primary">{{ Money::format($returnRequest->refund_cents) }}</span>
        </li>
    </ul>

    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Motivo') }}</dt>
            <dd class="text-primary">{{ __($returnRequest->reason->getLabel()) }}</dd>
        </div>
        @if ($returnRequest->comment)
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ __('Tu comentario') }}</dt>
                <dd class="whitespace-pre-line text-primary">{{ $returnRequest->comment }}</dd>
            </div>
        @endif
    </dl>
@endsection
