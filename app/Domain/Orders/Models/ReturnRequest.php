<?php

namespace App\Domain\Orders\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\ReturnReason;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Enums\ReturnType;
use App\Domain\Orders\Policies\ReturnRequestPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** A customer's request to return or exchange items of a completed order. */
#[Fillable([
    'number', 'order_id', 'customer_id', 'type', 'status', 'reason', 'comment', 'refund_cents',
    'staff_note', 'rejection_reason', 'locale',
    'requested_at', 'approved_at', 'rejected_at', 'received_at', 'resolved_at',
])]
#[UsePolicy(ReturnRequestPolicy::class)]
class ReturnRequest extends Model
{
    use HasUlids, LogsActivity;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'type' => ReturnType::class,
            'status' => ReturnStatus::class,
            'reason' => ReturnReason::class,
            'refund_cents' => 'integer',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'received_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<ReturnRequestItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('returns')
            ->logOnly(['status', 'staff_note', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
