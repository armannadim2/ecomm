<?php

namespace App\Domain\Orders\Cart;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Collection;

/**
 * Shopping cart kept in the session: ['variant ulid' => quantity].
 * Lines are rebuilt from the database every time, so prices and stock are always current;
 * variants that were deleted or unpublished drop out on their own.
 */
final class Cart
{
    private const KEY = 'cart';

    /** Shipping is taxed at the general rate. */
    private const SHIPPING_TAX_RATE = 21;

    /** @var Collection<int, CartLine>|null */
    private ?Collection $lines = null;

    public function __construct(private readonly CheckoutSettings $settings) {}

    /** @return Collection<int, CartLine> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $stored = $this->stored();

        if ($stored === []) {
            return $this->lines = collect();
        }

        $variants = ProductVariant::query()
            ->whereIn('ulid', array_keys($stored))
            ->with(['product' => fn ($q) => $q->with(['media', 'brand', 'categories' => fn ($c) => $c->where('is_visible', true)])])
            ->get()
            ->keyBy('ulid');

        $lines = collect();

        foreach ($stored as $ulid => $quantity) {
            $variant = $variants->get($ulid);

            if (! $variant || $variant->product?->status !== ProductStatus::Published) {
                continue;
            }

            $line = new CartLine($variant, $variant->product, (int) $quantity);
            $quantity = min($line->quantity, $line->maxQuantity());

            if ($quantity > 0) {
                $lines->push(new CartLine($variant, $variant->product, $quantity));
            }
        }

        return $this->lines = $lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    /** Adds units of a variant; never more than the available stock. Returns the quantity now in the cart. */
    public function add(ProductVariant $variant, int $quantity = 1): int
    {
        $variant->loadMissing('product');

        if ($variant->product?->status !== ProductStatus::Published) {
            throw new DomainRuleException(__('Este producto ya no está disponible.'));
        }

        $max = (new CartLine($variant, $variant->product, 0))->maxQuantity();

        if ($max < 1) {
            throw new DomainRuleException(__('Lo sentimos, este producto está agotado.'));
        }

        $stored = $this->stored();
        $new = min($max, ($stored[$variant->ulid] ?? 0) + max(1, $quantity));
        $stored[$variant->ulid] = $new;
        $this->save($stored);

        return $new;
    }

    public function update(string $variantUlid, int $quantity): void
    {
        $stored = $this->stored();

        if (! array_key_exists($variantUlid, $stored)) {
            return;
        }

        if ($quantity < 1) {
            unset($stored[$variantUlid]);
        } else {
            $stored[$variantUlid] = min(99, $quantity);
        }

        $this->save($stored);
    }

    public function remove(string $variantUlid): void
    {
        $this->update($variantUlid, 0);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function totals(Fulfillment $fulfillment = Fulfillment::Pickup): CartTotals
    {
        $lines = $this->lines();
        $subtotal = (int) $lines->sum(fn (CartLine $l) => $l->total());
        $shipping = $this->shippingFor($fulfillment, $subtotal);
        $tax = (int) $lines->sum(fn (CartLine $l) => $l->tax()) + self::taxIncluded($shipping, self::SHIPPING_TAX_RATE);
        $threshold = $this->settings->free_delivery_threshold_cents;

        return new CartTotals(
            itemCount: (int) $lines->sum('quantity'),
            subtotal: $subtotal,
            savings: (int) $lines->sum(fn (CartLine $l) => $l->savings()),
            shipping: $shipping,
            tax: $tax,
            missingForFreeDelivery: $this->settings->delivery_enabled && $threshold > 0 ? max(0, $threshold - $subtotal) : 0,
        );
    }

    public function shippingFor(Fulfillment $fulfillment, int $subtotal): int
    {
        if ($fulfillment === Fulfillment::Pickup) {
            return 0;
        }

        $threshold = $this->settings->free_delivery_threshold_cents;

        return $threshold > 0 && $subtotal >= $threshold ? 0 : $this->settings->delivery_cost_cents;
    }

    /** IVA contained in an amount that already includes it, rounded to the cent. */
    public static function taxIncluded(int $amount, int $rate): int
    {
        return (int) round($amount - $amount / (1 + $rate / 100));
    }

    /** @return array<string, int> */
    private function stored(): array
    {
        $stored = session(self::KEY, []);

        return is_array($stored) ? array_filter($stored, fn ($q) => is_int($q) && $q > 0) : [];
    }

    /** @param  array<string, int>  $stored */
    private function save(array $stored): void
    {
        session([self::KEY => $stored]);
        $this->lines = null;
    }
}
