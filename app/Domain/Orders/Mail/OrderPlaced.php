<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\Order;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Order receipt for the customer (Stitch "Email Receipt"), in the language they used at checkout. */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use OrderMailData, Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Tu pedido :number en :store', [
                'number' => $this->order->number,
                'store' => app(StoreSettings::class)->store_name,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.placed', with: $this->orderMailData($this->order));
    }
}
