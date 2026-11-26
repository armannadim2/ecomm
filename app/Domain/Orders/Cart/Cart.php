<?php

namespace App\Domain\Orders\Cart;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\ShippingZone;
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

    /**
     * Totals for a delivery choice. For home delivery the postal code picks the zone (local round,
     * Peninsula, Balearics); without one the Peninsula price is shown.
     */
    public function totals(Fulfillment $fulfillment = Fulfillment::Pickup, ?string $postalCode = null): CartTotals
    {
        $lines = $this->lines();
        $subtotal = (int) $lines->sum(fn (CartLine $l) => $l->total());
        $zone = $fulfillment === Fulfillment::Delivery
            ? (ShippingZone::fromPostalCode($postalCode, $this->settings) ?? ShippingZone::Peninsula)
            : null;
        $shipping = $zone ? $this->deliveryCost($zone, $subtotal) : 0;
        $tax = (int) $lines->sum(fn (CartLine $l) => $l->tax()) + self::taxIncluded($shipping, self::SHIPPING_TAX_RATE);
        $threshold = $this->settings->free_delivery_threshold_cents;

        return new CartTotals(
            itemCount: (int) $lines->sum('quantity'),
            subtotal: $subtotal,
            savings: (int) $lines->sum(fn (CartLine $l) => $l->savings()),
            shipping: $shipping,
            tax: $tax,
            missingForFreeDelivery: $this->settings->delivery_enabled && $threshold > 0 ? max(0, $threshold - $subtotal) : 0,
            zone: $zone,
        );
    }

    /** Delivery price for each zone with the current cart, for the checkout page. @return array<string, int> */
    public function deliveryQuotes(): array
    {
        $subtotal = (int) $this->lines()->sum(fn (CartLine $l) => $l->total());

        return collect(ShippingZone::cases())
            ->reject(fn (ShippingZone $z) => $z === ShippingZone::Local && ! $this->settings->local_delivery_enabled)
            ->mapWithKeys(fn (ShippingZone $z) => [$z->value => $this->deliveryCost($z, $subtotal)])
            ->all();
    }

    /** Parcel weight in grams (variants without a weight count as 0). */
    public function weightGrams(): int
    {
        return (int) $this->lines()->sum(fn (CartLine $l) => (int) ($l->variant->weight_grams ?? 0) * $l->quantity);
    }

    public function deliveryCost(ShippingZone $zone, int $subtotal): int
    {
        $threshold = $this->settings->free_delivery_threshold_cents;
        $freeApplies = $zone !== ShippingZone::Balearics || $this->settings->balearics_free_delivery;

        if ($threshold > 0 && $subtotal >= $threshold && $freeApplies) {
            return 0;
        }

        if ($zone === ShippingZone::Local) {
            return $this->settings->local_delivery_cost_cents;
        }

        $rate = $this->weightRate();

        return match ($zone) {
            ShippingZone::Balearics => $rate ? (int) $rate['balearics_cents'] : $this->settings->balearics_cost_cents,
            default => $rate ? (int) $rate['peninsula_cents'] : $this->settings->delivery_cost_cents,
        };
    }

    /** @return array{max_kg: float|int, peninsula_cents: int, balearics_cents: int}|null */
    private function weightRate(): ?array
    {
        $rates = collect($this->settings->weight_rates)
            ->filter(fn ($r) => is_array($r) && isset($r['max_kg'], $r['peninsula_cents'], $r['balearics_cents']))
            ->sortBy(fn ($r) => (float) $r['max_kg'])
            ->values();

        if ($rates->isEmpty()) {
            return null;
        }

        $kg = $this->weightGrams() / 1000;

        return $rates->first(fn ($r) => $kg <= (float) $r['max_kg']) ?? $rates->last();
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

    /**
     * After login: the cart saved on the customer's account (another device, an earlier visit) is
     * merged with this browser's cart. Quantities are added and later capped to the stock left.
     */
    public function mergeSaved(Customer $customer): void
    {
        $merged = $this->stored();

        foreach ((array) $customer->saved_cart as $ulid => $quantity) {
            if (is_string($ulid) && is_int($quantity) && $quantity > 0) {
                $merged[$ulid] = min(99, ($merged[$ulid] ?? 0) + $quantity);
            }
        }

        $this->save($merged);
        $this->save($this->lines()->mapWithKeys(fn (CartLine $l) => [$l->variant->ulid => $l->quantity])->all());
    }

    /** @param  array<string, int>  $stored */
    private function save(array $stored): void
    {
        session([self::KEY => $stored]);
        $this->lines = null;

        // Logged-in customers keep their cart on the account too.
        $customer = auth('web')->user();
        if ($customer instanceof Customer) {
            $customer->forceFill(['saved_cart' => $stored === [] ? null : $stored])->saveQuietly();
        }
    }
}
