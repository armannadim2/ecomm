<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Orders\Models\ReturnRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "We received your return/exchange request": sent when the customer submits it. */
class ReturnRequestReceived extends Mailable implements ShouldQueue
{
    use OrderMailData, Queueable, SerializesModels;

    public function __construct(public ReturnRequest $returnRequest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Hemos recibido tu solicitud :number', ['number' => $this->returnRequest->number]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.returns.status', with: [
            ...$this->orderMailData($this->returnRequest->order),
            'returnRequest' => $this->returnRequest->load('items'),
            'created' => true,
            'accountUrl' => route('account.returns.show', $this->returnRequest),
        ]);
    }
}
