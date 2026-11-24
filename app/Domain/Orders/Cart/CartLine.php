<?php

namespace App\Domain\Orders\Cart;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;

/** One line of the cart, with the current price and stock of the variant. */
final class CartLine
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly Product $product,
        public readonly int $quantity,
    ) {}

    public function unitPrice(): int
    {
        return $this->variant->price_cents;
    }

    public function compareAt(): ?int
    {
        $compare = $this->variant->compare_at_price_cents;

        return $compare && $compare > $this->unitPrice() ? $compare : null;
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function savings(): int
    {
        return $this->compareAt() ? ($this->compareAt() - $this->unitPrice()) * $this->quantity : 0;
    }

    public function taxRate(): int
    {
        return $this->product->tax_class->rate();
    }

    /** IVA contained in the (IVA-included) line total. */
    public function tax(): int
    {
        return Cart::taxIncluded($this->total(), $this->taxRate());
    }

    /** Most units the customer can buy (stock, or 99 for untracked items). */
    public function maxQuantity(): int
    {
        return $this->variant->track_stock ? max(0, min(99, $this->variant->stock)) : 99;
    }
}
