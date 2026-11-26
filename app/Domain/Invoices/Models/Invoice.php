<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Invoices\Enums\InvoiceType;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * An issued invoice. Immutable: it can be created but never updated or deleted — errors and refunds
 * are handled with a corrective invoice (series R). Amounts in signed cents, IVA included in totals.
 */
#[Fillable([
    'order_id', 'type', 'series', 'year', 'sequence', 'code', 'corrects_invoice_id', 'payment_refund_id', 'issued_at',
    'seller', 'buyer_name', 'buyer_tax_id', 'buyer_address', 'lines', 'tax_breakdown',
    'base_cents', 'tax_cents', 'total_cents', 'reason', 'previous_hash', 'hash',
])]
class Invoice extends Model
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Issued invoices cannot be changed; issue a corrective invoice.'));
        static::deleting(fn () => throw new LogicException('Issued invoices cannot be deleted; issue a corrective invoice.'));
    }

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'issued_at' => 'datetime',
            'seller' => 'array',
            'lines' => 'array',
            'tax_breakdown' => 'array',
            'base_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'year' => 'integer',
            'sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function corrects(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_invoice_id');
    }

    /** @return BelongsTo<PaymentRefund, $this> */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(PaymentRefund::class, 'payment_refund_id');
    }

    public function isCorrective(): bool
    {
        return $this->type === InvoiceType::Corrective;
    }

    public function fileName(): string
    {
        return 'factura-'.$this->code.'.pdf';
    }
}
