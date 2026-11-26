<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\PaymentRefund;
use Illuminate\Foundation\Events\Dispatchable;

/** Money went back to the customer. Issues the corrective invoice. */
final class OrderRefunded
{
    use Dispatchable;

    public function __construct(public readonly Order $order, public readonly PaymentRefund $refund) {}
}
