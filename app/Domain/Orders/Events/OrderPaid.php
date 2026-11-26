<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/** The money for an order has arrived (online payment confirmed, or paid at the counter). Issues the invoice. */
final class OrderPaid
{
    use Dispatchable;

    public function __construct(public readonly Order $order) {}
}
