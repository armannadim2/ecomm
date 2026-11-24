<?php

namespace App\Domain\Orders\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Policies\OrderPolicy;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A customer order. Amounts are integer cents with IVA included.
 * Contact, address and product data are copies taken at checkout.
 */
#[Fillable([
    'number', 'customer_id', 'status', 'fulfillment', 'payment_method', 'payment_status', 'pickup_code',
    'first_name', 'last_name', 'email', 'phone', 'tax_id',
    'ship_line1', 'ship_line2', 'ship_postal_code', 'ship_city', 'ship_province',
    'subtotal_cents', 'shipping_cents', 'total_cents', 'tax_cents', 'savings_cents',
    'locale', 'marketing_opt_in', 'customer_note', 'staff_note',
    'placed_at', 'paid_at', 'ready_at', 'completed_at', 'cancelled_at',
])]
#[UsePolicy(OrderPolicy::class)]
class Order extends Model
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
            'status' => OrderStatus::class,
            'fulfillment' => Fulfillment::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'marketing_opt_in' => 'boolean',
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'tax_cents' => 'integer',
            'savings_cents' => 'integer',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** @return list<string> */
    public function shippingLines(): array
    {
        return array_values(array_filter([
            $this->ship_line1,
            $this->ship_line2,
            trim(($this->ship_postal_code ?? '').' '.($this->ship_city ?? '')),
            $this->ship_province,
        ]));
    }

    /** Scannable QR (inline SVG) of the pickup code, for the counter. */
    public function pickupQrSvg(): ?string
    {
        if (! $this->pickup_code) {
            return null;
        }

        $svg = (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
        ])))->render($this->pickup_code);

        return preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('orders')
            ->logOnly(['status', 'payment_status', 'staff_note'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
