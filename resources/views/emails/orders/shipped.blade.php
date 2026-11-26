{{--
    "Your order is on its way" (home delivery). Same layout and colours as the Stitch order e-mails.
    Variables: $order, $store, $url, $whatsappUrl.
--}}
@extends('emails.layout')
@php use App\Support\Money; @endphp

@section('title', __('Tu pedido :number ya está en camino', ['number' => $order->number]))
@section('preheader', $order->tracking_number
    ? __('Seguimiento: :carrier :tracking', ['carrier' => $order->carrier, 'tracking' => $order->tracking_number])
    : __('Lo recibirás en la dirección que nos indicaste.'))
@section('footer_note', __('Te enviamos este aviso porque hiciste un pedido con envío a domicilio en :store.', ['store' => $store->store_name]))

@section('content')
    @include('emails.orders.partials.masthead')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#eed6cb;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#fce0d6;color:#862c14;font-size:11px;font-weight:700;">&#128666; {{ __('Envío a domicilio') }}</span>
                <h1 style="margin:10px 0 8px;font-size:28px;line-height:36px;font-weight:800;color:#1d3557;">{{ __('¡Hola, :name! Tu pedido ya está en camino', ['name' => $order->first_name]) }}</h1>
                <p style="margin:0;font-size:16px;line-height:24px;color:#4a5b76;">{{ __('Lo hemos entregado a la empresa de transporte. Normalmente llega en 24–48 horas laborables.') }}</p>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td class="tm-col" width="55%" style="padding:0 6px 8px 0;vertical-align:top;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdf1ec;border-radius:12px;">
                    <tr><td style="padding:20px;">
                        <div style="font-size:13px;font-weight:700;color:#c4431c;">&#128205; {{ __('Dirección de entrega') }}</div>
                        <div style="margin-top:6px;font-size:15px;line-height:22px;color:#1d3557;">
                            <strong>{{ $order->fullName() }}</strong><br>
                            @foreach ($order->shippingLines() as $line){{ $line }}<br>@endforeach
                        </div>
                    </td></tr>
                </table>
            </td>
            <td class="tm-col" width="45%" style="padding:0 0 8px 6px;vertical-align:top;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eed6cb;border-radius:12px;">
                    <tr><td style="padding:20px;">
                        <div style="font-size:13px;font-weight:700;color:#1d3557;">&#128230; {{ __('Seguimiento') }}</div>
                        @if ($order->tracking_number)
                            <div style="margin-top:6px;font-size:15px;line-height:22px;font-weight:700;color:#1d3557;">{{ $order->carrier }}</div>
                            <div style="font-size:15px;line-height:22px;color:#1d3557;">{{ $order->tracking_number }}</div>
                            @if ($order->tracking_url)
                                <a href="{{ $order->tracking_url }}" style="display:inline-block;margin-top:10px;padding:9px 14px;border-radius:8px;background:#c4431c;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">{{ __('Seguir mi envío') }}</a>
                            @endif
                        @else
                            <div style="margin-top:6px;font-size:13px;line-height:19px;color:#4a5b76;">{{ __('La empresa de transporte te avisará antes de la entrega.') }}</div>
                        @endif
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.orders.partials.items', ['heading' => __('Lo que va en el paquete')])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
        <tr>
            <td style="font-size:13px;color:#4a5b76;"><a href="{{ $url }}" style="color:#c4431c;font-weight:700;">{{ __('Ver mi pedido') }}</a></td>
            <td align="right" style="font-size:18px;font-weight:800;color:#1d3557;">{{ Money::format($order->total_cents) }}</td>
        </tr>
    </table>

    @include('emails.orders.partials.help')
@endsection
