<?php

namespace App\Domain\Invoices\Listeners;

use App\Domain\Invoices\Actions\IssueInvoice;
use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Events\OrderRefunded;

/** Issues the invoice when an order is paid and a corrective invoice for every refund. */
class IssueInvoiceForPaidOrder
{
    public function __construct(private readonly IssueInvoice $issue) {}

    public function handle(OrderPaid|OrderRefunded $event): void
    {
        $event instanceof OrderRefunded
            ? $this->issue->corrective($event->order, $event->refund)
            : $this->issue->forOrder($event->order);
    }
}
