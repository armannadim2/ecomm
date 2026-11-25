<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Models\Order;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * /api/v1/orders — online orders for the POS / back office.
 * Status changes follow the same rules as the admin (stock goes back when cancelled,
 * the customer gets the "ready to collect" e-mail…).
 */
class OrderController extends ApiController
{
    private const RELATIONS = ['items.product', 'items.variant', 'customer'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'fulfillment' => ['nullable', Rule::enum(Fulfillment::class)],
            'number' => ['nullable', 'string', 'max:32'],
            'pickup_code' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
        ]);

        $query = Order::query()->with(self::RELATIONS)->latest('id');
        foreach (['status', 'payment_status', 'fulfillment', 'number', 'pickup_code', 'email'] as $field) {
            $query->when($request->query($field), fn ($q, $value) => $q->where($field, $value));
        }
        $this->filterUpdatedSince($query, $request);

        return OrderResource::collection($query->paginate($this->perPage($request))->withQueryString());
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(self::RELATIONS));
    }

    /** {"status":"ready_for_pickup"} — only the next steps allowed for the order are accepted. */
    public function changeStatus(Request $request, Order $order, ChangeOrderStatus $change): OrderResource
    {
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);

        $change->handle($order, OrderStatus::from($data['status']));

        return new OrderResource($order->fresh(self::RELATIONS));
    }

    /** {"staff_note":"…"} — internal note, never shown to the customer. */
    public function update(Request $request, Order $order): OrderResource
    {
        $order->update($request->validate(['staff_note' => ['nullable', 'string', 'max:2000']]));

        return new OrderResource($order->fresh(self::RELATIONS));
    }
}
