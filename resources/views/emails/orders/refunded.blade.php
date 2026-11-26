{{--
    "We have refunded you" — sent for every refund. Variables: $order, $store, $url, $refund (PaymentRefund),
    $invoiceUrl (?string signed link to the corrective invoice), $invoiceCode (?string).
--}}
@extends('emails.layout')
@php use App\Support\Money; @endphp

@section('title', __('Te hemos devuelto dinero del pedido :number', ['number' => $order->number]))
@section('preheader', __('Reembolso de :amount.', ['amount' => Money::format($refund->amount_cents)]))
@section('footer_note', __('Te enviamos este aviso porque hiciste un pedido en :store.', ['store' => $store->store_name]))

@section('content')
    @include('emails.orders.partials.masthead')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#eed6cb;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <h1 style="margin:0 0 8px;font-size:26px;line-height:34px;font-weight:800;color:#1d3557;">{{ __('Hola, :name: te hemos devuelto :amount', ['name' => $order->first_name, 'amount' => Money::format($refund->amount_cents)]) }}</h1>
                <p style="margin:0;font-size:15px;line-height:23px;color:#4a5b76;">
                    @if ($refund->channel === \App\Domain\Payments\Models\PaymentRefund::CHANNEL_GATEWAY)
                        {{ __('El dinero vuelve a la misma forma de pago que usaste (:method). Según tu banco, puede tardar entre 3 y 10 días en aparecer.', ['method' => __($order->payment_method->getLabel())]) }}
                    @else
                        {{ __('Hemos registrado la devolución que te hicimos en la tienda o por transferencia.') }}
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#fdf1ec;border-radius:12px;">
        <tr><td style="padding:20px;font-size:14px;line-height:21px;color:#1d3557;">
            <strong>{{ __('Pedido') }}:</strong> {{ $order->number }}<br>
            <strong>{{ __('Importe devuelto') }}:</strong> {{ Money::format($refund->amount_cents) }}<br>
            <strong>{{ __('Motivo') }}:</strong> {{ $refund->reason }}
            @if ($invoiceUrl)
                <br><br><a href="{{ $invoiceUrl }}" style="display:inline-block;padding:9px 14px;border-radius:8px;background:#c4431c;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">{{ __('Descargar factura rectificativa :code', ['code' => $invoiceCode]) }}</a>
            @endif
        </td></tr>
    </table>

    <p style="margin:0 0 24px;font-size:13px;color:#4a5b76;"><a href="{{ $url }}" style="color:#c4431c;font-weight:700;">{{ __('Ver mi pedido') }}</a></p>

    @include('emails.orders.partials.help')
@endsection
