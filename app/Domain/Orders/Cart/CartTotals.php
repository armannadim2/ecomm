<?php

namespace App\Domain\Orders\Cart;

use App\Domain\Orders\Enums\ShippingZone;

/** Cart amounts in cents, IVA included. */
final class CartTotals
{
    public function __construct(
        public readonly int $itemCount,
        public readonly int $subtotal,
        public readonly int $savings,
        public readonly int $shipping,
        public readonly int $tax,
        /** Amount still needed for free home delivery (0 = already free or not applicable). */
        public readonly int $missingForFreeDelivery,
        /** Home delivery zone the shipping price is for (null = pickup). */
        public readonly ?ShippingZone $zone = null,
    ) {}

    public function total(): int
    {
        return $this->subtotal + $this->shipping;
    }

    public function taxBase(): int
    {
        return $this->total() - $this->tax;
    }
}
