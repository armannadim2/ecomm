<?php

namespace App\Http\Controllers\Store;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Actions\CreateReturnRequest;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\ReturnReason;
use App\Domain\Orders\Enums\ReturnType;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** "Devoluciones y cambios" in the customer account (backlog ORD-04). */
class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user();

        return view('store.account.returns.index', [
            'requests' => ReturnRequest::query()->where('customer_id', $customer->id)
                ->with(['order', 'items'])->latest('requested_at')->get(),
            'canRequest' => $this->requestableOrders($customer)->isNotEmpty(),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $orders = $this->requestableOrders($customer);
        $selected = $orders->firstWhere('ulid', $request->query('order')) ?? $orders->first();

        return view('store.account.returns.create', [
            'orders' => $orders,
            'selected' => $selected,
            'available' => $selected ? CreateReturnRequest::availableQuantities($selected) : [],
            'withdrawalDeadline' => $selected ? CreateReturnRequest::withdrawalDeadline($selected) : null,
        ]);
    }

    public function store(Request $request, CreateReturnRequest $create): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();

        $data = $request->validate([
            'order' => ['required', 'string'],
            'type' => ['required', Rule::enum(ReturnType::class)],
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:99'],
            'accept' => ['accepted'],
        ], [
            'accept.accepted' => __('Confirma que el producto está en buen estado y que has leído las condiciones.'),
        ]);

        $order = $customer->orders()->where('ulid', $data['order'])->with('items')->first();
        abort_unless($order, 404);

        try {
            $returnRequest = $create->handle(
                $customer,
                $order,
                ReturnType::from($data['type']),
                ReturnReason::from($data['reason']),
                $data['comment'] ?? null,
                $data['quantities'],
            );
        } catch (DomainRuleException $e) {
            return back()->withInput()->withErrors(['request' => $e->getMessage()]);
        }

        return redirect()->route('account.returns.show', $returnRequest)->with('status', __('Solicitud enviada. Te hemos escrito por correo.'));
    }

    public function show(Request $request, ReturnRequest $returnRequest): View
    {
        abort_unless($returnRequest->customer_id === $request->user()->id, 404);

        return view('store.account.returns.show', [
            'returnRequest' => $returnRequest->load(['order', 'items']),
        ]);
    }

    /**
     * Delivered orders of the customer that can still have units returned.
     *
     * @return Collection<int, Order>
     */
    private function requestableOrders(Customer $customer)
    {
        return $customer->orders()
            ->where('status', OrderStatus::Completed->value)
            ->with('items')
            ->get()
            ->filter(fn (Order $order) => CreateReturnRequest::isRequestable($order)
                && array_sum(CreateReturnRequest::availableQuantities($order)) > 0)
            ->values();
    }
}
