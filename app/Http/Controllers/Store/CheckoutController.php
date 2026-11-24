<?php

namespace App\Http\Controllers\Store;

use App\Domain\Orders\Actions\PlaceOrder;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /** Places the order and sends the customer to the confirmation page. */
    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder->handle($request->validated(), $request->user('web'));
        } catch (DomainRuleException $e) {
            return redirect()->route('cart.show')->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        // Lets this browser see the confirmation page without logging in.
        $request->session()->push('placed_orders', $order->ulid);

        return redirect()->route('order.show', $order);
    }

    /**
     * Order confirmation / status page (Stitch "Confirmació de Comanda").
     * Visible to the browser that placed it, the customer who owns it, or through the signed link in the e-mail.
     */
    public function show(Request $request, Order $order, CheckoutSettings $settings): View
    {
        $allowed = in_array($order->ulid, (array) $request->session()->get('placed_orders', []), true)
            || ($order->customer_id && $order->customer_id === $request->user('web')?->id)
            || $request->hasValidSignature();

        abort_unless($allowed, 404);

        $order->load(['items.product.media', 'items.product.categories']);

        return view('store.order', [
            'order' => $order,
            'settings' => $settings,
            'isPickup' => $order->fulfillment === Fulfillment::Pickup,
        ]);
    }
}
