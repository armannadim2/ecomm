{{--
    Return / exchange request e-mail: "received" (created) and every status change.
    Variables: $returnRequest (with items), $order, $store, $url (signed order page), $accountUrl, $created (bool),
    $hours, $mapUrl, $whatsappUrl. Layout shared with the order e-mails.
--}}
@extends('emails.layout')
@php
    use App\Domain\Orders\Enums\ReturnStatus;
    use App\Domain\Orders\Enums\ReturnType;
    use App\Support\Money;

    $status = $returnRequest->status;
    $isExchange = $returnRequest->type === ReturnType::Exchange;
    $headline = $created
        ? __('Hemos recibido tu solicitud')
        : match ($status) {
            ReturnStatus::Approved => __('Tu solicitud está aprobada'),
            ReturnStatus::Rejected => __('No hemos podido aceptar tu solicitud'),
            ReturnStatus::Received => __('Ya tenemos tu producto'),
            ReturnStatus::Refunded => __('Tu reembolso está hecho'),
            ReturnStatus::Exchanged => __('Tu cambio está listo'),
            default => __('Novedades de tu solicitud'),
        };
    $message = $created
        ? __('Revisaremos tu solicitud lo antes posible y te avisaremos por correo cuando la hayamos aprobado.')
        : match ($status) {
            ReturnStatus::Approved => __('Ya puedes traer el producto a la tienda. Enseña este correo o el número de solicitud en el mostrador. Si lo prefieres, escríbenos y lo organizamos.'),
            ReturnStatus::Rejected => __('Motivo: :reason. Si crees que es un error, escríbenos o pásate por la tienda y lo revisamos contigo.', ['reason' => $returnRequest->rejection_reason]),
            ReturnStatus::Received => $isExchange
                ? __('Hemos comprobado tu producto. Te avisaremos en cuanto el cambio esté listo.')
                : __('Hemos comprobado tu producto. Te avisaremos en cuanto hayamos hecho el reembolso.'),
            ReturnStatus::Refunded => __('Hemos devuelto :amount por los artículos de tu solicitud, con el mismo medio de pago que usaste (si pagaste en la tienda, en efectivo o por transferencia).', ['amount' => Money::format($returnRequest->refund_cents)]),
            ReturnStatus::Exchanged => __('Ya puedes recoger tu cambio en la tienda.'),
            default => '',
        };
@endphp

@section('title', $headline.' · '.$returnRequest->number)
@section('preheader', $message)
@section('footer_note', __('Te enviamos este aviso porque has pedido una devolución o un cambio en :store.', ['store' => $store->store_name]))

@section('content')
    @include('emails.orders.partials.masthead')

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#eed6cb;border-radius:12px;">
        <tr>
            <td class="tm-pad" style="padding:24px;">
                <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#fce0d6;color:#862c14;font-size:11px;font-weight:700;">{{ __($returnRequest->type->getLabel()) }} · {{ $returnRequest->number }}</span>
                <h1 style="margin:10px 0 8px;font-size:26px;line-height:34px;font-weight:800;color:#1d3557;">{{ $headline }}</h1>
                <p style="margin:0;font-size:16px;line-height:24px;color:#4a5b76;">{{ __('Hola, :name.', ['name' => $order->first_name]) }} {{ $message }}</p>
                <p style="margin:14px 0 0;font-size:13px;font-weight:700;color:#1d3557;">{{ __('Estado') }}: {{ __($status->getLabel()) }}</p>
            </td>
        </tr>
    </table>

    {{-- Items of the request --}}
    <div style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#4a5b76;">{{ __('Artículos de la solicitud') }} · {{ __('Pedido') }} {{ $order->number }}</div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
        @foreach ($returnRequest->items as $item)
            <tr>
                <td style="padding:10px 0;border-bottom:1px solid #f5e1d8;font-size:14px;line-height:20px;color:#1d3557;">
                    <strong>{{ $item->quantity }} × {{ $item->product_name }}</strong>
                    @if ($item->variant_name)<br><span style="font-size:12px;color:#4a5b76;">{{ $item->variant_name }}</span>@endif
                </td>
                <td align="right" style="padding:10px 0;border-bottom:1px solid #f5e1d8;font-size:14px;font-weight:700;color:#1d3557;white-space:nowrap;">{{ Money::format($item->totalCents()) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding:12px 0;font-size:13px;color:#4a5b76;">{{ $isExchange ? __('Valor de los artículos a cambiar') : __('Importe a reembolsar') }}</td>
            <td align="right" style="padding:12px 0;font-size:18px;font-weight:800;color:#1d3557;">{{ Money::format($returnRequest->refund_cents) }}</td>
        </tr>
    </table>

    {{-- Where to bring it: only when there is something to do in the shop --}}
    @if (! $created && $status === ReturnStatus::Approved)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;background:#fdf1ec;border-radius:12px;">
            <tr><td style="padding:20px;">
                <div style="font-size:13px;font-weight:700;color:#c4431c;">&#128205; {{ __('Dónde traerlo') }}</div>
                <div style="margin-top:4px;font-size:18px;line-height:24px;font-weight:700;color:#1d3557;">{{ $store->store_name }}</div>
                @if ($store->address_line)
                    <div style="font-size:15px;line-height:22px;color:#1d3557;">{{ $store->address_line }}, {{ trim($store->postal_code.' '.$store->city) }}</div>
                @endif
                @if (filled($hours))
                    <div style="margin-top:8px;font-size:13px;color:#4a5b76;">{{ __('Horario del mostrador') }}: {{ $hours }}</div>
                @endif
                <div style="margin-top:10px;font-size:12px;line-height:17px;color:#4a5b76;">{{ __('Trae el producto en buen estado, con sus accesorios y, a ser posible, en su embalaje.') }}</div>
                @if ($mapUrl)
                    <div style="margin-top:12px;"><a href="{{ $mapUrl }}" style="display:inline-block;padding:9px 14px;border-radius:8px;background:#c4431c;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">{{ __('Cómo llegar (Google Maps)') }}</a></div>
                @endif
            </td></tr>
        </table>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr><td>
            <a href="{{ $accountUrl }}" style="display:inline-block;padding:12px 20px;border-radius:8px;background:#c4431c;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">{{ __('Ver mi solicitud') }}</a>
        </td></tr>
    </table>

    @include('emails.orders.partials.help')
@endsection
