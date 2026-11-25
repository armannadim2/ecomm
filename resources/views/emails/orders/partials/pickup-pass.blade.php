{{--
    Click & Collect pass: QR + pickup code + hours + map link.
    Variables: $order, $store, $hours (string), $title, $mapUrl (?string), $message (when sending).
    The QR is attached to the e-mail (cid:) because most mail apps block inline SVG and data: images.
--}}
@php
    $png = $order->pickupQrPng();
    $qrSrc = $png === null ? null : (isset($message)
        ? $message->embedData($png, 'recogida-'.$order->pickup_code.'.png', 'image/png')
        : 'data:image/png;base64,'.base64_encode($png));
@endphp
@if ($order->pickup_code)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#1d3557;border-radius:12px;">
    <tr>
        <td class="tm-pad" style="padding:24px;color:#ffffff;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                <tr>
                    <td style="font-size:18px;line-height:24px;font-weight:700;color:#ffffff;">&#9889; {{ $title }}</td>
                    <td align="right" class="tm-hide-sm"><span style="display:inline-block;padding:2px 10px;border-radius:999px;background:#2b4a73;color:#fce0d6;font-size:11px;font-weight:700;">{{ __('Sin hacer la cola general') }}</span></td>
                </tr>
            </table>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#2b4a73;border-radius:8px;">
                <tr>
                    <td class="tm-col" width="180" align="center" style="padding:16px;vertical-align:middle;">
                        <div style="background:#ffffff;border-radius:6px;padding:10px;display:inline-block;">
                            @if ($qrSrc)
                                <img src="{{ $qrSrc }}" width="128" height="128" alt="{{ __('Código QR de recogida :code', ['code' => $order->pickup_code]) }}" style="display:block;width:128px;height:128px;">
                            @endif
                            <div style="margin-top:4px;font-family:Consolas,Menlo,monospace;font-size:11px;font-weight:700;color:#1d3557;letter-spacing:.08em;">{{ $order->pickup_code }}</div>
                        </div>
                    </td>
                    <td class="tm-col" style="padding:16px;vertical-align:middle;color:#ffffff;">
                        <div style="font-size:11px;line-height:14px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#fce0d6;">{{ __('Código de recogida en el mostrador') }}</div>
                        <div style="font-family:Consolas,Menlo,monospace;font-size:26px;line-height:34px;font-weight:700;color:#ffffff;">{{ $order->pickup_code }}</div>
                        <p style="margin:6px 0 10px;font-size:12px;line-height:17px;color:#b9c7e7;">
                            {!! __('Enseña este código en el mostrador de <strong>Click & Collect</strong> o di tu nombre y número de pedido.') !!}
                        </p>
                        @if (filled($hours))
                            <div style="font-size:12px;line-height:17px;color:#ffffff;">&#128339; {{ $hours }}</div>
                        @endif
                        @if ($store->address_line)
                            <div style="font-size:12px;line-height:17px;color:#ffffff;">&#128205; {{ $store->address_line }}, {{ trim($store->postal_code.' '.$store->city) }}</div>
                        @endif
                    </td>
                </tr>
            </table>
            @if ($mapUrl)
                <div style="margin-top:16px;">
                    <a href="{{ $mapUrl }}" style="display:inline-block;padding:9px 16px;border-radius:8px;background:#ffffff;color:#1d3557;font-size:13px;font-weight:700;text-decoration:none;">&#128506; {{ __('Abrir en Google Maps') }}</a>
                </div>
            @endif
        </td>
    </tr>
</table>
@endif
