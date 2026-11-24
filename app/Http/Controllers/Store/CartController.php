<?php

namespace App\Http\Controllers\Store;

use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Orders\Cart\Cart;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Cart and checkout page (Stitch "Cistella i Pagament"). */
class CartController extends Controller
{
    public function show(Request $request, Cart $cart, CheckoutSettings $settings): View
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

        return view('store.cart', [
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

    public function add(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'variant' => ['required', 'string', 'max:30'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $variant = ProductVariant::query()->where('ulid', $data['variant'])->first();

        if (! $variant) {
            return back()->withErrors(['cart' => __('Este producto ya no está disponible.')]);
        }

        try {
            $cart->add($variant, (int) ($data['quantity'] ?? 1));
        } catch (DomainRuleException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        return redirect()->route('cart.show')->with('status', __('«:product» se ha añadido a tu cesta.', ['product' => $variant->product->name]));
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
