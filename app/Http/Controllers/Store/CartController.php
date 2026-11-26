<?php

namespace App\Http\Controllers\Store;

use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Orders\Cart\Cart;
use App\Domain\Orders\Cart\CartLine;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Cart and checkout page (Stitch "Cistella i Pagament"). */
class CartController extends Controller
{
    public function show(Request $request, Cart $cart, CheckoutSettings $settings, PaymentSettings $payments): View
    {
        $fulfillment = Fulfillment::tryFrom((string) old('fulfillment')) ?? ($settings->pickup_enabled ? Fulfillment::Pickup : Fulfillment::Delivery);
        $customer = $request->user('web');
        $lines = $cart->lines();

        $crossSell = $lines->isEmpty() ? collect() : StorefrontProducts::query()
            ->whereNotIn('id', $lines->map(fn ($l) => $l->product->id))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $lines->flatMap(fn ($l) => $l->product->categories->pluck('id'))))
            ->whereHas('variants', fn ($q) => $q->where(fn ($v) => $v->where('track_stock', false)->orWhere('stock', '>', 0)))
            ->inRandomOrder()
            ->limit(2)
            ->get();

        // One token per checkout: submitting the form twice never creates two orders (PlaceOrder).
        if (! $request->session()->has('checkout_token')) {
            $request->session()->put('checkout_token', (string) Str::ulid());
        }

        return view('store.cart', [
            'checkoutToken' => $request->session()->get('checkout_token'),
            'deliveryQuotes' => $cart->deliveryQuotes(),
            'deliveryAvailable' => $settings->delivery_enabled && $payments->anyOnlineReady(),
            'lines' => $lines,
            'totals' => [
                'pickup' => $cart->totals(Fulfillment::Pickup),
                'delivery' => $cart->totals(Fulfillment::Delivery),
            ],
            'fulfillment' => $fulfillment,
            'settings' => $settings,
            'paymentMethods' => PaymentMethod::cases(),
            'customer' => $customer,
            'address' => $customer?->addresses()->orderByDesc('is_default')->first(),
            'crossSell' => $crossSell,
        ]);
    }

    /**
     * Adds to the cart and stays on the page: JSON for the storefront script (header cart updated
     * in place, mini cart opened), otherwise back to the page the customer was on.
     */
    public function add(Request $request, Cart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'variant' => ['required', 'string', 'max:30'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $variant = ProductVariant::query()->where('ulid', $data['variant'])->first();

        try {
            if (! $variant) {
                throw new DomainRuleException(__('Este producto ya no está disponible.'));
            }
            $cart->add($variant, (int) ($data['quantity'] ?? 1));
        } catch (DomainRuleException $e) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->withErrors(['cart' => $e->getMessage()]);
        }

        $message = __('«:product» se ha añadido a tu cesta.', ['product' => $variant->product->name]);

        return $request->expectsJson()
            ? response()->json(['message' => $message] + $this->summaryData($cart))
            : back()->with('status', $message);
    }

    /** Mini cart (header popup): read in the browser, so it also works on cached pages. */
    public function summary(Cart $cart): JsonResponse
    {
        return response()->json($this->summaryData($cart))->header('Cache-Control', 'no-store, private');
    }

    /** @return array<string, mixed> */
    private function summaryData(Cart $cart): array
    {
        $count = $cart->count();

        return [
            'count' => $count,
            'subtotal' => Money::format($cart->totals()->subtotal),
            'label' => trans_choice('Ver carrito, :count artículo|Ver carrito, :count artículos', $count),
            'lines' => $cart->lines()->map(fn (CartLine $line) => [
                'name' => $line->product->name,
                'variant' => $line->variant->name,
                'quantity' => $line->quantity,
                'total' => Money::format($line->total()),
                'image' => $line->product->imageUrl('thumb'),
                'url' => route('store.product', $line->product->slug),
            ])->values()->all(),
        ];
    }

    public function update(Request $request, Cart $cart, string $variant): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart->update($variant, (int) $data['quantity']);

        return redirect()->route('cart.show');
    }

    public function remove(Cart $cart, string $variant): RedirectResponse
    {
        $cart->remove($variant);

        return redirect()->route('cart.show')->with('status', __('Artículo eliminado de la cesta.'));
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('cart.show')->with('status', __('Tu cesta está vacía.'));
    }
}
