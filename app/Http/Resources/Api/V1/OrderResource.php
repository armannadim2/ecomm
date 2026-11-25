<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'number' => $this->number,
            'status' => $this->status->value,
            'payment_status' => $this->payment_status->value,
            'payment_method' => $this->payment_method->value,
            'fulfillment' => $this->fulfillment->value,
            'pickup_code' => $this->pickup_code,
            'locale' => $this->locale,
            'customer' => [
                'id' => $this->whenLoaded('customer', fn () => $this->customer?->ulid),
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'tax_id' => $this->tax_id,
                'marketing_opt_in' => (bool) $this->marketing_opt_in,
            ],
            'shipping_address' => $this->ship_line1 ? [
                'line1' => $this->ship_line1,
                'line2' => $this->ship_line2,
                'postal_code' => $this->ship_postal_code,
                'city' => $this->ship_city,
                'province' => $this->ship_province,
                'country' => 'ES',
            ] : null,
            'totals' => [
                'currency' => 'EUR',
                'subtotal_cents' => $this->subtotal_cents,
                'savings_cents' => $this->savings_cents,
                'shipping_cents' => $this->shipping_cents,
                'tax_cents' => $this->tax_cents,
                'total_cents' => $this->total_cents,
            ],
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (OrderItem $item) => [
                'product_id' => $item->product?->ulid,
                'variant_id' => $item->variant?->ulid,
                'sku' => $item->sku,
                'name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'compare_at_price_cents' => $item->compare_at_price_cents,
                'tax_rate' => (float) $item->tax_rate,
                'line_total_cents' => $item->line_total_cents,
            ])->values()),
            'customer_note' => $this->customer_note,
            'staff_note' => $this->staff_note,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'ready_at' => $this->ready_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
