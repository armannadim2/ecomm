<?php

namespace App\Domain\Invoices\Actions;

use App\Domain\Invoices\Enums\InvoiceType;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Orders\Cart\Cart;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Payments\Models\PaymentRefund;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\DB;

/**
 * Issues invoices with sequential numbers per series and year (S simplified, F full, R corrective).
 *  - forOrder(): when an order is paid. A NIF (or an amount over the simplified-invoice limit) gives a full invoice.
 *  - corrective(): for each refund, a negative invoice that refers to the original one.
 * Every invoice stores a SHA-256 fingerprint chained to the previous invoice (VeriFactu-ready; nothing is
 * sent to the tax agency yet — that needs a certified VeriFactu provider or the AEAT web service).
 */
class IssueInvoice
{
    /** Shipping is taxed at the general rate (same as the cart). */
    private const SHIPPING_TAX_RATE = 21;

    public function __construct(private readonly StoreSettings $store) {}

    public function forOrder(Order $order): Invoice
    {
        if ($existing = $order->invoices()->where('type', '!=', InvoiceType::Corrective)->first()) {
            return $existing;
        }

        $order->loadMissing('items');

        $lines = $order->items->map(fn (OrderItem $item) => [
            'description' => trim($item->product_name.($item->variant_name ? ' — '.$item->variant_name : '')),
            'sku' => $item->sku,
            'quantity' => $item->quantity,
            'unit_price_cents' => $item->unit_price_cents,
            'tax_rate' => $item->tax_rate,
            'total_cents' => $item->line_total_cents,
        ])->values()->all();

        if ($order->shipping_cents > 0) {
            $lines[] = [
                'description' => 'Gastos de envío',
                'sku' => null,
                'quantity' => 1,
                'unit_price_cents' => $order->shipping_cents,
                'tax_rate' => self::SHIPPING_TAX_RATE,
                'total_cents' => $order->shipping_cents,
            ];
        }

        $full = filled($order->tax_id) || $order->total_cents > InvoiceType::SIMPLIFIED_LIMIT_CENTS;

        return $this->issue($order, $full ? InvoiceType::Full : InvoiceType::Simplified, $lines, self::breakdown($lines));
    }

    public function corrective(Order $order, PaymentRefund $refund): Invoice
    {
        if ($existing = Invoice::query()->where('payment_refund_id', $refund->id)->first()) {
            return $existing;
        }

        $original = $this->forOrder($order);

        // Spread the refunded amount over the original IVA rates, in proportion; the last rate takes the rounding.
        $rows = collect($original->tax_breakdown)->filter(fn ($r) => $r['total_cents'] > 0)->values();
        $originalTotal = max(1, (int) $rows->sum('total_cents'));
        $left = $refund->amount_cents;
        $lines = [];

        foreach ($rows as $i => $row) {
            $share = $i === $rows->count() - 1 ? $left : (int) round($refund->amount_cents * $row['total_cents'] / $originalTotal);
            $share = min($share, $left);
            $left -= $share;

            if ($share > 0) {
                $lines[] = [
                    'description' => 'Reembolso: '.$refund->reason,
                    'sku' => null,
                    'quantity' => 1,
                    'unit_price_cents' => -$share,
                    'tax_rate' => (int) $row['rate'],
                    'total_cents' => -$share,
                ];
            }
        }

        return $this->issue($order, InvoiceType::Corrective, $lines, self::breakdown($lines), $original, $refund);
    }

    /**
     * @param  list<array{total_cents: int, tax_rate: int}>  $lines
     * @return list<array{rate: int, base_cents: int, tax_cents: int, total_cents: int}>
     */
    public static function breakdown(array $lines): array
    {
        return collect($lines)
            ->groupBy('tax_rate')
            ->map(function ($group, $rate) {
                $total = (int) $group->sum('total_cents');
                $tax = (int) $group->sum(fn ($l) => ($l['total_cents'] < 0 ? -1 : 1) * Cart::taxIncluded(abs((int) $l['total_cents']), (int) $rate));

                return ['rate' => (int) $rate, 'base_cents' => $total - $tax, 'tax_cents' => $tax, 'total_cents' => $total];
            })
            ->sortByDesc('rate')
            ->values()
            ->all();
    }

    private function issue(Order $order, InvoiceType $type, array $lines, array $breakdown, ?Invoice $corrects = null, ?PaymentRefund $refund = null): Invoice
    {
        return DB::transaction(function () use ($order, $type, $lines, $breakdown, $corrects, $refund): Invoice {
            $now = now();
            $year = (int) $now->timezone('Europe/Madrid')->year;
            $series = $type->series();

            DB::table('invoice_sequences')->insertOrIgnore(['series' => $series, 'year' => $year, 'last_number' => 0]);
            $sequenceRow = DB::table('invoice_sequences')->where('series', $series)->where('year', $year)->lockForUpdate()->first();
            $number = (int) $sequenceRow->last_number + 1;
            DB::table('invoice_sequences')->where('id', $sequenceRow->id)->update(['last_number' => $number]);

            $previousHash = Invoice::query()->orderByDesc('id')->lockForUpdate()->value('hash');
            $code = sprintf('%s%d-%06d', $series, $year, $number);
            $seller = [
                'name' => $this->store->legal_name ?: $this->store->store_name,
                'trade_name' => $this->store->store_name,
                'tax_id' => $this->store->nif,
                'address' => $this->store->fullAddress(),
            ];
            $tax = (int) collect($breakdown)->sum('tax_cents');
            $total = (int) collect($breakdown)->sum('total_cents');

            $buyerAddress = $order->shippingLines() !== [] ? implode(', ', $order->shippingLines()) : null;

            return Invoice::query()->create([
                'order_id' => $order->id,
                'type' => $type,
                'series' => $series,
                'year' => $year,
                'sequence' => $number,
                'code' => $code,
                'corrects_invoice_id' => $corrects?->id,
                'payment_refund_id' => $refund?->id,
                'issued_at' => $now,
                'seller' => $seller,
                'buyer_name' => $order->fullName(),
                'buyer_tax_id' => $order->tax_id,
                'buyer_address' => $buyerAddress,
                'lines' => $lines,
                'tax_breakdown' => $breakdown,
                'base_cents' => $total - $tax,
                'tax_cents' => $tax,
                'total_cents' => $total,
                'reason' => $refund?->reason,
                'previous_hash' => $previousHash,
                'hash' => hash('sha256', implode('|', [
                    $seller['tax_id'] ?? '', $code, $now->format('Y-m-d H:i:s'), $type->value, $tax, $total, $previousHash ?? '',
                ])),
            ]);
        });
    }
}
