<?php

namespace App\Domain\Orders\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_item_id', 'product_name', 'variant_name', 'quantity', 'unit_price_cents'])]
class ReturnRequestItem extends Model
{
    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_cents' => 'integer'];
    }

    /** @return BelongsTo<ReturnRequest, $this> */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function totalCents(): int
    {
        return $this->quantity * $this->unit_price_cents;
    }
}
