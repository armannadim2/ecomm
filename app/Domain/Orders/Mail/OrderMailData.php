<?php

namespace App\Domain\Orders\Mail;

use App\Domain\Content\Blocks\Block;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\URL;

/**
 * Data shared by the order e-mails (layout: resources/views/emails/layout.blade.php).
 * Texts per language come from the settings; the mail is rendered in the order's language.
 */
trait OrderMailData
{
    /** @return array<string, mixed> */
    protected function orderMailData(Order $order): array
    {
        $checkout = app(CheckoutSettings::class);
        $texts = new Block('mail', ['hours' => $checkout->opening_hours, 'note' => $checkout->pickup_note]);

        return [
            'order' => $order->loadMissing('items.product.media'),
            'store' => app(StoreSettings::class),
            // Signed link: lets a guest open the order page from the e-mail.
            'url' => URL::signedRoute('order.show', $order),
            'hours' => $texts->text('hours'),
            'pickupNote' => $texts->text('note'),
            'mapUrl' => Block::resolveUrl('[mapa]'),
            'whatsappUrl' => Block::resolveUrl('[whatsapp]'),
        ];
    }
}
