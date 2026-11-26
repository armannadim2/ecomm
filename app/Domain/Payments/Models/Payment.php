<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One attempt to pay an order through Redsys or PayPal. */
#[Fillable([
    'order_id', 'gateway', 'method', 'amount_cents', 'refunded_cents', 'status', 'reference', 'transaction_id',
    'response_code', 'failure_reason', 'gateway_data', 'paid_at', 'failed_at',
])]
class Payment extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            'gateway' => Gateway::class,
            'method' => PaymentMethod::class,
            'status' => PaymentAttemptStatus::class,
            'amount_cents' => 'integer',
            'refunded_cents' => 'integer',
            'gateway_data' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<PaymentRefund, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function refundableCents(): int
    {
        return $this->status === PaymentAttemptStatus::Paid ? max(0, $this->amount_cents - $this->refunded_cents) : 0;
    }
}
