<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Models\PaymentRefund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/** "We have refunded you X €", with a link to the corrective invoice. */
class OrderRefunded extends Mailable implements ShouldQueue
{
    use OrderMailData, Queueable, SerializesModels;

    public function __construct(public Order $order, public PaymentRefund $refund)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Te hemos devuelto dinero del pedido :number', ['number' => $this->order->number]));
    }

    public function content(): Content
    {
        $invoice = $this->order->invoices()->where('payment_refund_id', $this->refund->id)->first();

        return new Content(view: 'emails.orders.refunded', with: $this->orderMailData($this->order) + [
            'refund' => $this->refund,
            'invoiceUrl' => $invoice ? URL::temporarySignedRoute('invoice.download', now()->addDays(30), $invoice) : null,
            'invoiceCode' => $invoice?->code,
        ]);
    }
}
