{{--
    "Your order is ready" for Click & Collect (Stitch "Email Comanda a Punt per Recollir").
    Variables: $order, $store, $url, $hours, $mapUrl, $whatsappUrl, $pickupNote. Sent when staff mark it ready.
--}}
@extends('emails.layout')
@php use App\Support\Money; @endphp

@section('title', __('Tu pedido :number está listo para recoger', ['number' => $order->number]))
@section('preheader', __('Ven a recogerlo a :address. Código de recogida: :code.', ['address' => $store->address_line ?: $store->store_name, 'code' => $order->pickup_code]))
@section('footer_note', __('Te enviamos este aviso porque hiciste un pedido con recogida en tienda en :store.', ['store' => $store->store_name]))

@section('content')
    @include('emails.orders.partials.masthead')

    {{-- Hero --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#eee7e2;border-radius:12px;">
        <tr>
            <td class="tm-col tm-pad" style="padding:24px;vertical-align:middle;">
                <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#ffdbd0;color:#390b00;font-size:11px;font-weight:700;">&#127978; {{ __('Mostrador Click & Collect · Servicio exprés') }}</span>
                <h1 style="margin:10px 0 8px;font-size:28px;line-height:36px;font-weight:800;color:#05142c;">{{ __('¡Hola, :name! Tu pedido ya te espera en la tienda', ['name' => $order->first_name]) }} &#127881;</h1>
                <p style="margin:0;font-size:16px;line-height:24px;color:#44474d;">
                    {!! __('Nuestro equipo de :address ha revisado y empaquetado tu pedido con mucho cuidado. ¡Ya puedes pasar a recogerlo sin hacer la cola de caja!', ['address' => '<strong style="color:#05142c;">'.e($store->address_line ?: $store->store_name).'</strong>']) !!}
                </p>
            </td>
            <td class="tm-col" width="170" align="center" style="padding:24px;vertical-align:middle;">
                <table role="presentation" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;">
                    <tr><td align="center" style="padding:16px;">
                        <div style="font-size:30px;line-height:34px;">&#9200;</div>
                        <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#44474d;">{{ __('Estado del pedido') }}</div>
                        <div style="font-size:15px;font-weight:800;color:#05142c;">{{ __('Listo para recoger') }}</div>
                        @if ($order->ready_at)
                            <div style="font-size:12px;color:#44474d;">{{ __('Preparado a las :time', ['time' => $order->ready_at->timezone('Europe/Madrid')->format('H:i')]) }}</div>
                        @endif
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.orders.partials.pickup-pass', ['title' => __('Pase de recogida prioritario')])

    {{-- Where & when --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td class="tm-col" width="58%" style="padding:0 6px 8px 0;vertical-align:top;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#faf2ed;border-radius:12px;">
                    <tr><td style="padding:20px;">
                        <div style="font-size:13px;font-weight:700;color:#ab3504;">&#128205; {{ __('Punto de recogida') }}</div>
                        <div style="margin-top:4px;font-size:18px;line-height:24px;font-weight:700;color:#05142c;">{{ $store->store_name }}</div>
                        @if ($store->address_line)
                            <div style="font-size:15px;line-height:22px;color:#1e1b18;">{{ $store->address_line }}, {{ trim($store->postal_code.' '.$store->city) }}</div>
                        @endif
                        <div style="margin-top:14px;">
                            @if ($mapUrl)
                                <a href="{{ $mapUrl }}" style="display:inline-block;margin:0 6px 6px 0;padding:9px 14px;border-radius:8px;background:#ab3504;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">&#129517; {{ __('Cómo llegar (Google Maps)') }}</a>
                            @endif
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" style="display:inline-block;margin:0 6px 6px 0;padding:9px 14px;border-radius:8px;background:#e9e1dc;color:#05142c;font-size:13px;font-weight:700;text-decoration:none;">&#128172; {{ __('Avisar por WhatsApp') }}</a>
                            @endif
                        </div>
                    </td></tr>
                </table>
            </td>
            <td class="tm-col" width="42%" style="padding:0 0 8px 6px;vertical-align:top;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eee7e2;border-radius:12px;">
                    <tr><td style="padding:20px;">
                        <div style="font-size:13px;font-weight:700;color:#05142c;">&#128339; {{ __('Horario del mostrador') }}</div>
                        <div style="margin-top:6px;font-size:15px;line-height:22px;font-weight:700;color:#05142c;">{{ filled($hours) ? $hours : __('Consulta el horario en la web') }}</div>
                        @if (filled($pickupNote))
                            <div style="margin-top:10px;padding:10px;border-radius:8px;background:#ffffff;font-size:12px;line-height:17px;color:#44474d;">{{ $pickupNote }}</div>
                        @endif
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- What to bring --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#faf2ed;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <div style="font-size:18px;line-height:24px;font-weight:700;color:#05142c;">&#9989; {{ __('¿Qué necesitas para recoger tu pedido?') }}</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
                    <tr>
                        <td class="tm-col" width="50%" style="padding:0 4px 8px 0;vertical-align:top;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;"><tr>
                                <td width="40" style="padding:14px 0 14px 14px;vertical-align:top;"><div style="width:28px;height:28px;border-radius:999px;background:#05142c;color:#ffffff;text-align:center;line-height:28px;font-size:13px;font-weight:700;">1</div></td>
                                <td style="padding:14px;font-size:12px;line-height:17px;color:#44474d;"><strong style="display:block;font-size:13px;color:#05142c;">{{ __('Código o QR del pedido') }}</strong>{!! __('Basta con enseñar el QR de este correo en el móvil o decir el código :code.', ['code' => '<strong>'.e($order->pickup_code).'</strong>']) !!}</td>
                            </tr></table>
                        </td>
                        <td class="tm-col" width="50%" style="padding:0 0 8px 4px;vertical-align:top;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;"><tr>
                                <td width="40" style="padding:14px 0 14px 14px;vertical-align:top;"><div style="width:28px;height:28px;border-radius:999px;background:#05142c;color:#ffffff;text-align:center;line-height:28px;font-size:13px;font-weight:700;">2</div></td>
                                <td style="padding:14px;font-size:12px;line-height:17px;color:#44474d;"><strong style="display:block;font-size:13px;color:#05142c;">{{ __('Documento de identidad') }}</strong>{!! __('DNI, NIE o pasaporte de la persona titular: :name.', ['name' => '<strong style="color:#1e1b18;">'.e($order->fullName()).'</strong>']) !!}</td>
                            </tr></table>
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:4px;background:#ffeee8;border-radius:8px;">
                    <tr><td style="padding:14px;font-size:12px;line-height:17px;color:#1e1b18;">
                        <strong style="display:block;font-size:13px;color:#05142c;">&#128101; {{ __('¿Puede venir otra persona a recogerlo?') }}</strong>
                        {{ __('¡Claro que sí! Solo tiene que enseñar el QR de este correo en el mostrador y llevar una foto de tu documento de identidad en el móvil.') }}
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    @include('emails.orders.partials.items', ['heading' => __('Artículos preparados en este paquete')])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
        <tr>
            <td style="font-size:13px;color:#44474d;">{{ $order->payment_status === \App\Domain\Orders\Enums\PaymentStatus::Paid ? __('Pagado con :method', ['method' => __($order->payment_method->getLabel())]) : __('Pagarás en la tienda al recogerlo') }}</td>
            <td align="right" style="font-size:18px;font-weight:800;color:#05142c;">{{ Money::format($order->total_cents) }}</td>
        </tr>
    </table>

    @include('emails.orders.partials.help')

    {{-- Need help banner --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eee7e2;border-radius:12px;">
        <tr>
            <td style="padding:16px;">
                <div style="font-size:13px;line-height:18px;font-weight:700;color:#05142c;">{{ __('¿Necesitas cambiar la recogida?') }}</div>
                <div style="font-size:12px;line-height:16px;color:#44474d;">
                    @if ($store->email){{ __('Escríbenos a :email indicando el pedido :number.', ['email' => $store->email, 'number' => $order->number]) }}@else{{ __('Indícanos el pedido :number.', ['number' => $order->number]) }}@endif
                </div>
            </td>
            @if ($store->email)
                <td align="right" style="padding:16px;">
                    <a href="mailto:{{ $store->email }}?subject={{ rawurlencode(__('Pedido').' '.$order->number) }}" style="display:inline-block;padding:9px 14px;border-radius:8px;background:#ffffff;color:#05142c;font-size:12px;font-weight:700;text-decoration:none;white-space:nowrap;">{{ __('Contactar con la tienda') }}</a>
                </td>
            @endif
        </tr>
    </table>
@endsection
