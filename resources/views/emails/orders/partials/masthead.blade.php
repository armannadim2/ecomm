{{-- Brand + order number. Variables: $store, $order, $url (signed order page). --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
        <td class="tm-col" style="vertical-align:middle;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:48px;height:48px;background:#1b2942;border-radius:12px;text-align:center;vertical-align:middle;font-size:24px;line-height:48px;color:#fe6f3f;">&#128717;</td>
                    <td style="padding-left:12px;">
                        <div style="font-size:22px;line-height:28px;font-weight:800;color:#05142c;">
                            {{ \Illuminate\Support\Str::before($store->store_name, '&') }}@if (str_contains($store->store_name, '&'))<span style="color:#ab3504;">&amp;</span>{{ \Illuminate\Support\Str::after($store->store_name, '&') }}@endif
                            <span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:999px;background:#ffdbd0;color:#390b00;font-size:10px;line-height:14px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;vertical-align:middle;">{{ __('Tienda online') }}</span>
                        </div>
                        @if ($store->address_line)
                            <div style="font-size:12px;line-height:16px;color:#44474d;">{{ $store->address_line }} · {{ trim($store->postal_code.' '.$store->city) }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
        <td class="tm-col" align="right" style="vertical-align:middle;padding-top:8px;">
            <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#f4ece7;font-size:11px;line-height:14px;font-weight:700;color:#05142c;">
                {{ __('Pedido') }} <span style="color:#ab3504;">{{ $order->number }}</span>
            </span>
            <div style="margin-top:4px;font-size:11px;"><a href="{{ $url }}" style="color:#44474d;">{{ __('Ver en el navegador') }}</a></div>
        </td>
    </tr>
</table>
