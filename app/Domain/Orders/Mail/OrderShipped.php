<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "Your order is on its way", with the carrier and tracking number. Sent when staff mark it shipped. */
class OrderShipped extends Mailable implements ShouldQueue
{
    use OrderMailData, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Tu pedido :number ya está en camino', ['number' => $this->order->number]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.shipped', with: $this->orderMailData($this->order));
    }
}
