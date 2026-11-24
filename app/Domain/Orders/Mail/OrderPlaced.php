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
use Illuminate\Support\Facades\URL;

/** Order confirmation for the customer, in the language they used at checkout. */
class OrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
        return new Content(markdown: 'emails.orders.placed', with: [
            'order' => $this->order->loadMissing('items'),
            'store' => app(StoreSettings::class),
            // Signed link: lets a guest open the order page from the e-mail.
            'url' => URL::signedRoute('order.show', $this->order),
        ]);
    }
}
