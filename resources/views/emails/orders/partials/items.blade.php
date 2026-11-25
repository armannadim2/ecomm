{{-- Ordered articles. Variables: $order (with items.product), $heading. --}}
@php use App\Support\Money; @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
    <tr>
        <td style="font-size:18px;line-height:24px;font-weight:600;color:#1d3557;">{{ $heading }} ({{ $order->itemCount() }})</td>
    </tr>
</table>
@foreach ($order->items as $item)
    @php
        $image = $item->product?->imageUrl('thumb');
        $discount = $item->compare_at_price_cents && $item->compare_at_price_cents > $item->unit_price_cents
            ? (int) round(100 - $item->unit_price_cents * 100 / $item->compare_at_price_cents) : null;
    @endphp
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;background:#fdf1ec;border-radius:12px;">
        <tr>
            <td width="80" style="padding:12px 0 12px 12px;vertical-align:middle;">
                @if ($image)
                    <img src="{{ $image }}" width="64" height="64" alt="" style="display:block;width:64px;height:64px;border-radius:8px;object-fit:cover;background:#ffffff;">
                @else
                    <div style="width:64px;height:64px;border-radius:8px;background:#ffffff;text-align:center;line-height:64px;font-size:24px;">&#128230;</div>
                @endif
            </td>
            <td style="padding:12px;vertical-align:middle;">
                <div style="font-size:12px;line-height:16px;color:#4a5b76;">
                    @if ($discount)<span style="display:inline-block;padding:1px 6px;border-radius:4px;background:#fce0d6;color:#862c14;font-size:10px;font-weight:700;">-{{ $discount }}%</span>@endif
                    @if ($item->sku) {{ __('Ref.') }}: {{ $item->sku }}@endif
                </div>
                <div style="font-size:15px;line-height:20px;font-weight:700;color:#1d3557;">{{ $item->product_name }}</div>
                @if ($item->variant_name)<div style="font-size:12px;line-height:16px;color:#4a5b76;">{{ $item->variant_name }}</div>@endif
            </td>
            <td align="right" style="padding:12px;vertical-align:middle;white-space:nowrap;">
                <div style="font-size:11px;line-height:14px;color:#4a5b76;">{{ __('Cantidad') }}: {{ $item->quantity }}</div>
                @if ($discount)
                    <div style="font-size:12px;color:#596579;text-decoration:line-through;">{{ Money::format($item->compare_at_price_cents * $item->quantity) }}</div>
                @endif
                <div style="font-size:18px;line-height:24px;font-weight:700;color:#1d3557;">{{ Money::format($item->line_total_cents) }}</div>
            </td>
        </tr>
    </table>
@endforeach
