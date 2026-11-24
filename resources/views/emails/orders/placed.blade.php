<x-mail::message>
# {{ __('¡Gracias por tu pedido, :name!', ['name' => $order->first_name]) }}

{{ __('Hemos recibido tu pedido :number.', ['number' => $order->number]) }}
@if ($order->fulfillment === \App\Domain\Orders\Enums\Fulfillment::Pickup)
{{ __('Te avisaremos cuando esté listo para recoger en la tienda.') }}

**{{ __('Código de recogida') }}: {{ $order->pickup_code }}**
@else
{{ __('Te avisaremos cuando salga hacia tu dirección.') }}
@endif

<x-mail::table>
| {{ __('Artículo') }} | {{ __('Cant.') }} | {{ __('Importe') }} |
|:--|:--:|--:|
@foreach ($order->items as $item)
| {{ $item->product_name }}@if ($item->variant_name) ({{ $item->variant_name }})@endif | {{ $item->quantity }} | {{ \App\Support\Money::format($item->line_total_cents) }} |
@endforeach
| **{{ __('Total') }}** | | **{{ \App\Support\Money::format($order->total_cents) }}** |
</x-mail::table>

{{ __('Forma de pago') }}: {{ __($order->payment_method->getLabel()) }} · {{ __('IVA incluido') }}

<x-mail::button :url="$url">
{{ __('Ver mi pedido') }}
</x-mail::button>

{{ $store->store_name }}@if ($store->address_line) · {{ $store->fullAddress() }}@endif
</x-mail::message>
