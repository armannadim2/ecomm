{{--
    Order receipt (Stitch "Email Receipt & Factura - Tot & Més Barcelona").
    Variables: $order, $store, $url, $hours, $mapUrl, $whatsappUrl. Sent in the order's language.
    Not a fiscal invoice: invoice numbering (factura simplificada / Verifactu) is not built yet.
--}}
@extends('emails.layout')
@php
    use App\Domain\Orders\Enums\Fulfillment;
    use App\Domain\Orders\Enums\PaymentStatus;
    use App\Support\Money;
    $pickup = $order->fulfillment === Fulfillment::Pickup;
    $paid = $order->payment_status === PaymentStatus::Paid;
    $catalogue = $order->subtotal_cents + $order->savings_cents;
@endphp

@section('title', __('Tu pedido :number en :store', ['number' => $order->number, 'store' => $store->store_name]))
@section('preheader', __('Hemos recibido tu pedido :number. Total: :total.', ['number' => $order->number, 'total' => Money::format($order->total_cents)]))
@section('footer_note', __('Este mensaje es el comprobante de tu compra en :store. Si no has hecho este pedido, escríbenos.', ['store' => $store->store_name]))

@section('content')
    @include('emails.orders.partials.masthead')

    {{-- Greeting & status --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#faf2ed;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size:18px;line-height:24px;font-weight:600;color:#05142c;">{{ __('¡Hola, :name!', ['name' => $order->first_name]) }} &#128075;</td>
                        <td align="right">
                            <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#97f5cb;color:#002115;font-size:11px;font-weight:700;">
                                &#10004; {{ $paid ? __('Pago confirmado (:method)', ['method' => __($order->payment_method->getLabel())]) : __('Pedido confirmado') }}
                            </span>
                        </td>
                    </tr>
                </table>
                <p style="margin:12px 0 0;font-size:16px;line-height:24px;color:#1e1b18;">
                    @if ($pickup)
                        {!! __('Muchas gracias por confiar en el comercio de tu barrio. Hemos recibido tu pedido por <strong>:total</strong> y ya lo estamos preparando en la tienda.', ['total' => e(Money::format($order->total_cents))]) !!}
                    @else
                        {!! __('Muchas gracias por confiar en el comercio de tu barrio. Hemos recibido tu pedido por <strong>:total</strong> y lo prepararemos para enviarlo a tu dirección.', ['total' => e(Money::format($order->total_cents))]) !!}
                    @endif
                </p>
                {{-- Progress --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
                    <tr>
                        <td style="font-size:11px;line-height:14px;font-weight:700;color:#05142c;">{{ __('Estado: :status', ['status' => __($order->status->getLabel())]) }}</td>
                        <td align="right" style="font-size:11px;line-height:14px;font-weight:700;color:#ab3504;">{{ $pickup ? __('Te avisaremos cuando esté listo para recoger') : __('Te avisaremos cuando salga hacia tu dirección') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:6px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e9e1dc;border-radius:999px;">
                                <tr><td width="33%" style="height:8px;line-height:8px;font-size:0;background:#ab3504;border-radius:999px;">&nbsp;</td><td style="font-size:0;">&nbsp;</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($pickup)
        @include('emails.orders.partials.pickup-pass', ['title' => __('Pase de recogida exprés en tienda')])
    @endif

    @include('emails.orders.partials.items', ['heading' => __('Artículos comprados')])

    {{-- Summary (amounts include IVA) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0 24px;background:#faf2ed;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <div style="font-size:18px;line-height:24px;font-weight:600;color:#05142c;">&#129534; {{ __('Resumen de la compra') }}</div>
                <div style="margin-bottom:16px;font-size:12px;line-height:16px;color:#44474d;">{{ __('Comprobante de compra y de garantía') }}</div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;margin-bottom:16px;">
                    <tr>
                        <td class="tm-col" width="50%" style="padding:16px;vertical-align:top;font-size:12px;line-height:18px;color:#1e1b18;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#44474d;">{{ __('Vendedor') }}</div>
                            <strong style="color:#05142c;">{{ $store->legal_name ?: $store->store_name }}</strong><br>
                            @if ($store->nif){{ __('NIF') }}: {{ $store->nif }}<br>@endif
                            @if ($store->address_line){{ $store->fullAddress() }}@endif
                        </td>
                        <td class="tm-col" width="50%" style="padding:16px;vertical-align:top;font-size:12px;line-height:18px;color:#1e1b18;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#44474d;">{{ __('Cliente') }}</div>
                            <strong style="color:#05142c;">{{ $order->fullName() }}</strong><br>
                            @if ($order->tax_id){{ __('NIF / DNI') }}: {{ $order->tax_id }}<br>@endif
                            @if ($order->phone){{ __('Teléfono') }}: {{ $order->phone }}<br>@endif
                            {{ __('Forma de pago') }}: {{ __($order->payment_method->getLabel()) }}
                            @if (! $pickup && $order->shippingLines())
                                <br>{{ __('Envío a') }}: {{ implode(', ', $order->shippingLines()) }}
                            @endif
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:22px;color:#44474d;">
                    @if ($order->savings_cents > 0)
                        <tr><td>{{ __('Subtotal a precio de catálogo') }}</td><td align="right">{{ Money::format($catalogue) }}</td></tr>
                        <tr style="color:#ab3504;"><td>{{ __('Descuentos') }}</td><td align="right">-{{ Money::format($order->savings_cents) }}</td></tr>
                    @else
                        <tr><td>{{ __('Subtotal') }}</td><td align="right">{{ Money::format($order->subtotal_cents) }}</td></tr>
                    @endif
                    <tr>
                        <td>{{ $pickup ? __('Recogida en tienda') : __('Envío') }}</td>
                        <td align="right" style="{{ $order->shipping_cents === 0 ? 'color:#41a17b;font-weight:700;text-transform:uppercase;' : '' }}">{{ $order->shipping_cents === 0 ? __('Gratis') : Money::format($order->shipping_cents) }}</td>
                    </tr>
                    <tr><td>{{ __('Base imponible') }}</td><td align="right">{{ Money::format($order->total_cents - $order->tax_cents) }}</td></tr>
                    <tr><td>{{ __('IVA') }}</td><td align="right">{{ Money::format($order->tax_cents) }}</td></tr>
                </table>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;background:#e9e1dc;border-radius:8px;">
                    <tr>
                        <td style="padding:16px;">
                            <div style="font-size:22px;line-height:28px;font-weight:800;color:#05142c;">{{ __('Total') }}</div>
                            <div style="font-size:12px;line-height:16px;color:#44474d;">{{ __('IVA incluido') }}{{ $paid ? ' · '.__('Pagado') : '' }}</div>
                        </td>
                        <td align="right" style="padding:16px;">
                            <div style="font-size:24px;line-height:28px;font-weight:800;color:#ab3504;">{{ Money::format($order->total_cents) }}</div>
                            <div style="font-size:11px;font-weight:700;color:#003020;">{{ __($order->payment_method->getLabel()) }}</div>
                        </td>
                    </tr>
                </table>

                <div style="margin-top:16px;">
                    <a href="{{ $url }}" style="display:inline-block;padding:11px 18px;border-radius:8px;background:#05142c;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">{{ __('Ver mi pedido') }}</a>
                </div>
            </td>
        </tr>
    </table>

    @include('emails.orders.partials.help')

    @if ($pickup && $store->address_line)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4ece7;border-radius:12px;">
            <tr>
                <td style="padding:16px;">
                    <div style="font-size:13px;line-height:18px;font-weight:700;color:#05142c;">&#128694; {{ __('Recogida en :address', ['address' => $store->address_line]) }}</div>
                    <div style="font-size:12px;line-height:16px;color:#44474d;">{{ trim($store->postal_code.' '.$store->city) }}@if (filled($hours)) · {{ $hours }}@endif</div>
                </td>
                @if ($mapUrl)
                    <td align="right" style="padding:16px;">
                        <a href="{{ $mapUrl }}" style="display:inline-block;padding:8px 14px;border-radius:999px;background:#ffffff;color:#05142c;font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap;">{{ __('Cómo llegar') }}</a>
                    </td>
                @endif
            </tr>
        </table>
    @endif
@endsection
