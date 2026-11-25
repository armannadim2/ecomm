<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "Your order is ready to collect" (Stitch "Email Comanda a Punt per Recollir"), sent when staff mark it ready. */
class OrderReadyForPickup extends Mailable implements ShouldQueue
{
    use OrderMailData, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Tu pedido :number está listo para recoger', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.ready', with: $this->orderMailData($this->order));
    }
}
