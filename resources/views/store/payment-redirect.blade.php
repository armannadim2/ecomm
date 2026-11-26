@extends('store.layouts.app')

{{-- Auto-submitted form that takes the customer to the Redsys payment page. Variables: $redirect (PaymentRedirect), $order (Order) --}}

@section('title', __('Redirigiendo al pago seguro'))
@section('compactHeader', true)

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <div class="mx-auto flex max-w-md flex-col items-center gap-4 rounded-3xl border border-outline-variant/60 bg-white p-8 text-center shadow-sm">
        @svg('heroicon-o-lock-closed', 'size-10 text-secondary')
        <h1 class="text-xl font-extrabold text-primary">{{ __('Te llevamos a la pasarela de pago segura') }}</h1>
        <p class="text-sm text-on-surface-variant">{{ __('Pedido :number · Pagarás en la página de tu banco; nosotros nunca vemos los datos de tu tarjeta.', ['number' => $order->number]) }}</p>

        <form id="payment-redirect" method="post" action="{{ $redirect->url }}" class="w-full">
            @foreach ($redirect->fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="w-full rounded-xl bg-secondary px-5 py-3 text-sm font-bold text-white shadow-cta transition-colors hover:bg-secondary-hover">
                {{ __('Continuar al pago') }}
            </button>
        </form>
        <noscript><p class="text-xs text-on-surface-variant">{{ __('Pulsa «Continuar al pago» si no se abre solo.') }}</p></noscript>
    </div>

    <script>
        document.getElementById('payment-redirect').submit();
    </script>
@endsection
