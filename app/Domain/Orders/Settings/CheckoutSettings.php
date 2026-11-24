<?php

namespace App\Domain\Orders\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Delivery options and prices for the checkout. Edited in "Envíos y pagos".
 * Amounts in cents, IVA included.
 */
class CheckoutSettings extends Settings
{
    public bool $pickup_enabled;

    public bool $delivery_enabled;

    public int $delivery_cost_cents;

    /** Free home delivery from this cart total (0 = never free). */
    public int $free_delivery_threshold_cents;

    public bool $pay_in_store_enabled;

    /** Shown on the pickup option, e.g. "A punto hoy antes de las 14:00 h". Per language {es, ca, en}. */
    public array $pickup_note;

    /** Opening hours line shown on pickup and confirmation pages. Per language {es, ca, en}. */
    public array $opening_hours;

    public static function group(): string
    {
        return 'checkout';
    }
}
