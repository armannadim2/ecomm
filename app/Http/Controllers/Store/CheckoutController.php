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
    /** Places the order; online payments continue at the gateway, the rest goes to the confirmation page. */
    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        try {
            $order = $placeOrder->handle($request->validated(), $request->user('web'));
        } catch (DomainRuleException $e) {
            return redirect()->route('cart.show')->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        // Lets this browser see the confirmation page without logging in; a new token for the next checkout.
        if (! in_array($order->ulid, (array) $request->session()->get('placed_orders', []), true)) {
            $request->session()->push('placed_orders', $order->ulid);
        }
        $request->session()->forget('checkout_token');

        if ($order->isAwaitingOnlinePayment()) {
            return redirect()->route('payment.start', $order);
        }

        return redirect()->route('order.show', $order);
    }

    /**
     * Order confirmation / status page (Stitch "Confirmació de Comanda").
     * Visible to the browser that placed it, the customer who owns it, or through a signed link (e-mail, bank return).
     */
    public function show(Request $request, Order $order, CheckoutSettings $settings): View
    {
        abort_unless(self::canSee($request, $order), 404);

        $order->load(['items.product.media', 'items.product.categories', 'invoices']);

        return view('store.order', [
            'order' => $order,
            'settings' => $settings,
            'isPickup' => $order->fulfillment === Fulfillment::Pickup,
        ]);
    }

    public static function canSee(Request $request, Order $order): bool
    {
        return in_array($order->ulid, (array) $request->session()->get('placed_orders', []), true)
            || ($order->customer_id && $order->customer_id === $request->user('web')?->id)
            || $request->hasValidSignature();
    }
}
