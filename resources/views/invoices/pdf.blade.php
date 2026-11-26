{{--
    Invoice PDF (dompdf: plain HTML 4 + inline CSS, DejaVu Sans for € and accents).
    Variables: $invoice (Invoice with order, corrects)
--}}
@php
    use App\Support\Money;
    $seller = $invoice->seller;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->type->getLabel() }} {{ $invoice->code }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1b2a41; }
        h1 { font-size: 18px; margin: 0 0 2px; color: #a93119; }
        .muted { color: #5b6474; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 0 0 14px; }
        .box { border: 1px solid #d7dbe2; border-radius: 4px; padding: 8px 10px; }
        .lines th { background: #f2f3f6; text-align: left; padding: 6px; font-size: 9px; text-transform: uppercase; }
        .lines td { padding: 6px; border-bottom: 1px solid #eceef2; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 55%; margin-left: 45%; margin-top: 12px; }
        .totals td { padding: 4px 6px; }
        .grand td { font-size: 13px; font-weight: bold; border-top: 2px solid #1b2a41; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8px; color: #7a8291; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td>
                <h1>{{ $invoice->type->getLabel() }}</h1>
                <div><strong>{{ __('Número') }}:</strong> {{ $invoice->code }}</div>
                <div><strong>{{ __('Fecha de expedición') }}:</strong> {{ $invoice->issued_at->timezone('Europe/Madrid')->format('d/m/Y') }}</div>
                <div><strong>{{ __('Pedido') }}:</strong> {{ $invoice->order->number }}</div>
                @if ($invoice->corrects)
                    <div><strong>{{ __('Rectifica la factura') }}:</strong> {{ $invoice->corrects->code }} ({{ $invoice->corrects->issued_at->timezone('Europe/Madrid')->format('d/m/Y') }})</div>
                    <div><strong>{{ __('Motivo') }}:</strong> {{ $invoice->reason }}</div>
                @endif
            </td>
            <td class="num" style="vertical-align: top;">
                <strong style="font-size: 14px;">{{ $seller['trade_name'] ?? $seller['name'] }}</strong>
            </td>
        </tr>
    </table>

    <table class="parties" style="margin-top: 14px;">
        <tr>
            <td style="padding-right: 8px;">
                <div class="box">
                    <div class="muted">{{ __('Emisor') }}</div>
                    <strong>{{ $seller['name'] }}</strong><br>
                    {{ __('NIF') }}: {{ $seller['tax_id'] ?: '—' }}<br>
                    {{ $seller['address'] }}
                </div>
            </td>
            <td style="padding-left: 8px;">
                <div class="box">
                    <div class="muted">{{ __('Destinatario') }}</div>
                    <strong>{{ $invoice->buyer_name }}</strong><br>
                    @if ($invoice->buyer_tax_id) {{ __('NIF') }}: {{ $invoice->buyer_tax_id }}<br> @endif
                    @if ($invoice->buyer_address) {{ $invoice->buyer_address }} @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('Concepto') }}</th>
                <th class="num">{{ __('Cant.') }}</th>
                <th class="num">{{ __('Precio (IVA incl.)') }}</th>
                <th class="num">{{ __('IVA') }}</th>
                <th class="num">{{ __('Importe') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line['description'] }} @if (! empty($line['sku']))<span class="muted">· {{ $line['sku'] }}</span>@endif</td>
                    <td class="num">{{ $line['quantity'] }}</td>
                    <td class="num">{{ Money::format($line['unit_price_cents']) }}</td>
                    <td class="num">{{ $line['tax_rate'] }} %</td>
                    <td class="num">{{ Money::format($line['total_cents']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        @foreach ($invoice->tax_breakdown as $row)
            <tr>
                <td>{{ __('Base imponible al :rate %', ['rate' => $row['rate']]) }}</td>
                <td class="num">{{ Money::format($row['base_cents']) }}</td>
            </tr>
            <tr>
                <td>{{ __('Cuota de IVA :rate %', ['rate' => $row['rate']]) }}</td>
                <td class="num">{{ Money::format($row['tax_cents']) }}</td>
            </tr>
        @endforeach
        <tr class="grand">
            <td>{{ __('Total') }}</td>
            <td class="num">{{ Money::format($invoice->total_cents) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $seller['name'] }} · {{ __('NIF') }} {{ $seller['tax_id'] }} · {{ __('Huella') }}: {{ substr($invoice->hash, 0, 16) }}…
        @if ($invoice->order->payment_method)
            · {{ __('Forma de pago') }}: {{ __($invoice->order->payment_method->getLabel()) }}
        @endif
    </div>
</body>
</html>
