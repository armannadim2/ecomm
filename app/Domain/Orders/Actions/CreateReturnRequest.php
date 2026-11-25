<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\ReturnReason;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Enums\ReturnType;
use App\Domain\Orders\Mail\ReturnRequestReceived;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Orders\Models\ReturnRequestItem;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * A customer asks to return or exchange items of one of their completed orders.
 *
 * Rules come from the "Envíos, recogida y devoluciones" legal page (App\Domain\Content\LegalTexts):
 * 14 natural days from delivery for the right of withdrawal, and the 3-year legal guarantee for
 * defective or wrong products (no 14-day limit).
 */
class CreateReturnRequest
{
    public const WITHDRAWAL_DAYS = 14;

    public const GUARANTEE_YEARS = 3;

    /** Last moment to return an order without giving a reason (null while the order is not delivered). */
    public static function withdrawalDeadline(Order $order): ?CarbonImmutable
    {
        return $order->completed_at
            ? CarbonImmutable::instance($order->completed_at)->timezone('Europe/Madrid')->addDays(self::WITHDRAWAL_DAYS)->endOfDay()
            : null;
    }

    /** Last moment to claim the legal guarantee for the order. */
    public static function guaranteeDeadline(Order $order): ?CarbonImmutable
    {
        return $order->completed_at
            ? CarbonImmutable::instance($order->completed_at)->timezone('Europe/Madrid')->addYears(self::GUARANTEE_YEARS)->endOfDay()
            : null;
    }

    /** Can this order be shown in the request form at all? */
    public static function isRequestable(Order $order): bool
    {
        return $order->status === OrderStatus::Completed
            && self::guaranteeDeadline($order)?->isFuture() === true;
    }

    /**
     * Units of each order item that can still be requested, keyed by order item id.
     * Units already in a request that was not rejected are not available again.
     *
     * @return array<int, int>
     */
    public static function availableQuantities(Order $order): array
    {
        $taken = ReturnRequestItem::query()
            ->whereHas('returnRequest', fn ($q) => $q->where('order_id', $order->id)->where('status', '!=', ReturnStatus::Rejected->value))
            ->selectRaw('order_item_id, SUM(quantity) as total')
            ->groupBy('order_item_id')
            ->pluck('total', 'order_item_id');

        return $order->items->mapWithKeys(fn ($item) => [$item->id => max(0, $item->quantity - (int) ($taken[$item->id] ?? 0))])->all();
    }

    /**
     * @param  array<int|string, int|string>  $quantities  order item id => units to return (0 or missing = not returned)
     *
     * @throws DomainRuleException
     */
    public function handle(Customer $customer, Order $order, ReturnType $type, ReturnReason $reason, ?string $comment, array $quantities): ReturnRequest
    {
        if ($order->customer_id !== $customer->id) {
            throw new DomainRuleException(__('Este pedido no es tuyo.'));
        }

        if ($order->status !== OrderStatus::Completed) {
            throw new DomainRuleException(__('Solo puedes devolver o cambiar pedidos ya entregados.'));
        }

        $withdrawal = self::withdrawalDeadline($order);

        if (! $reason->isGuarantee() && $withdrawal?->isPast()) {
            throw new DomainRuleException(__('El plazo de 14 días para devolver este pedido terminó el :date. Si el producto es defectuoso o no es el que pediste, elige ese motivo: la garantía legal dura 3 años.', [
                'date' => $withdrawal->locale(app()->getLocale())->isoFormat('LL'),
            ]));
        }

        if (self::guaranteeDeadline($order)?->isPast()) {
            throw new DomainRuleException(__('Este pedido ya no se puede devolver: pasó el plazo de la garantía legal.'));
        }

        return DB::transaction(function () use ($customer, $order, $type, $reason, $comment, $quantities): ReturnRequest {
            // Lock the order row so two requests at the same time cannot return the same units twice.
            Order::query()->whereKey($order->id)->lockForUpdate()->first();
            $order->load('items');
            $available = self::availableQuantities($order);

            $lines = [];
            foreach ($order->items as $item) {
                $wanted = (int) ($quantities[$item->id] ?? 0);
                if ($wanted <= 0) {
                    continue;
                }
                if ($wanted > ($available[$item->id] ?? 0)) {
                    throw new DomainRuleException(__('Has pedido devolver más unidades de «:product» de las disponibles.', ['product' => $item->product_name]));
                }
                $lines[] = [
                    'order_item_id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity' => $wanted,
                    'unit_price_cents' => $item->unit_price_cents,
                ];
            }

            if ($lines === []) {
                throw new DomainRuleException(__('Elige al menos un artículo.'));
            }

            $request = ReturnRequest::query()->create([
                'number' => 'tmp-'.Str::ulid(),
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'type' => $type,
                'status' => ReturnStatus::Requested,
                'reason' => $reason,
                'comment' => filled($comment) ? trim($comment) : null,
                'refund_cents' => (int) collect($lines)->sum(fn (array $l) => $l['quantity'] * $l['unit_price_cents']),
                'locale' => $order->locale,
                'requested_at' => now(),
            ]);
            $request->items()->createMany($lines);
            $request->update(['number' => sprintf('DEV-%s-%05d', now()->year, $request->id)]);

            // Sent after commit (the mailable is afterCommit).
            Mail::to($order->email)->locale($order->locale)->queue(new ReturnRequestReceived($request));

            return $request;
        });
    }
}
