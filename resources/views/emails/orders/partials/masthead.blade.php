{{-- Brand + order number. Variables: $store, $order, $url (signed order page). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td class="tm-col" style="vertical-align:middle;">
            {{-- Official logo (PNG: most mail apps do not show SVG). Brand minimum width on screen: 120 px. --}}
            <img src="{{ asset('brand/totimes-horizontal-color.png') }}" width="183" height="44" alt="{{ $store->store_name }}"
                 style="display:block;width:183px;height:auto;border:0;">
            @if ($store->address_line)
                <div style="margin-top:8px;font-size:12px;line-height:16px;color:#4a5b76;">{{ $store->address_line }} · {{ trim($store->postal_code.' '.$store->city) }}</div>
            @endif
        </td>
        <td class="tm-col" align="right" style="vertical-align:middle;padding-top:8px;">
            <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#f5e1d8;font-size:11px;line-height:14px;font-weight:700;color:#1d3557;">
                {{ __('Pedido') }} <span style="color:#c4431c;">{{ $order->number }}</span>
            </span>
            <div style="margin-top:4px;font-size:11px;"><a href="{{ $url }}" style="color:#4a5b76;">{{ __('Ver en el navegador') }}</a></div>
        </td>
    </tr>
</table>
