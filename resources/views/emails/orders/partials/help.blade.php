{{-- Guarantee + contact cards. Variables: $store, $whatsappUrl (?string). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
    <tr>
        <td class="tm-col" width="50%" style="padding:0 4px 8px 0;vertical-align:top;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdf1ec;border-radius:12px;">
                <tr><td style="padding:16px;">
                    <div style="font-size:13px;line-height:18px;font-weight:700;color:#1d3557;">&#128737; {{ __('Garantía legal de 3 años') }}</div>
                    <p style="margin:4px 0 0;font-size:12px;line-height:16px;color:#4a5b76;">{{ __('Guarda este correo: es tu comprobante de compra para cambios y garantía en la tienda.') }}</p>
                </td></tr>
            </table>
        </td>
        <td class="tm-col" width="50%" style="padding:0 0 8px 4px;vertical-align:top;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fdf1ec;border-radius:12px;">
                <tr><td style="padding:16px;">
                    <div style="font-size:13px;line-height:18px;font-weight:700;color:#1d3557;">&#128172; {{ __('¿Dudas con tu pedido?') }}</div>
                    <p style="margin:4px 0 0;font-size:12px;line-height:16px;color:#4a5b76;">
                        @if ($store->phone && $whatsappUrl)
                            {!! __('Escríbenos por WhatsApp al :phone o ven a vernos a la tienda.', ['phone' => '<a href="'.e($whatsappUrl).'" style="color:#1d3557;font-weight:700;">'.e($store->phone).'</a>']) !!}
                        @elseif ($store->email)
                            {!! __('Escríbenos a :email o ven a vernos a la tienda.', ['email' => '<a href="mailto:'.e($store->email).'" style="color:#1d3557;font-weight:700;">'.e($store->email).'</a>']) !!}
                        @else
                            {{ __('Ven a vernos a la tienda.') }}
                        @endif
                    </p>
                </td></tr>
            </table>
        </td>
    </tr>
</table>
