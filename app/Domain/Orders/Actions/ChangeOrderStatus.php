<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

/**
 * Moves an order to its next status. Cancelling puts the stock back.
 * Completing a pay-in-store order marks it as paid (the customer paid at the counter).
 */
class ChangeOrderStatus
{
    public function __construct(private readonly AdjustStock $adjustStock) {}

    public function handle(Order $order, OrderStatus $to): Order
    {
        if (! in_array($to, $order->status->next($order->fulfillment), true)) {
            throw new DomainRuleException("No se puede pasar de «{$order->status->getLabel()}» a «{$to->getLabel()}».");
        }

        return DB::transaction(function () use ($order, $to): Order {
            $changes = ['status' => $to];

            match ($to) {
                OrderStatus::ReadyForPickup, OrderStatus::Shipped => $changes['ready_at'] = now(),
                OrderStatus::Completed => $changes['completed_at'] = now(),
                OrderStatus::Cancelled => $changes['cancelled_at'] = now(),
                default => null,
            };

            if ($to === OrderStatus::Completed && $order->payment_method === PaymentMethod::PayInStore) {
                $changes['payment_status'] = PaymentStatus::Paid;
                $changes['paid_at'] = now();
            }

            if ($to === OrderStatus::Cancelled) {
                foreach ($order->items()->with('variant')->get() as $item) {
                    if ($item->variant && $item->variant->track_stock) {
                        $this->adjustStock->handle($item->variant, $item->quantity, 'Pedido '.$order->number.' cancelado');
                    }
                }
            }

            $order->update($changes);

            return $order;
        });
    }
}
