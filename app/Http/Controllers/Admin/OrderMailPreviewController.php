<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Mail\OrderReadyForPickup;
use App\Domain\Orders\Mail\OrderShipped;
use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;

/** Shows staff an order e-mail as the customer receives it (admin → order → "Ver correos"). */
class OrderMailPreviewController extends Controller
{
    private const MAILS = [
        'confirmacion' => OrderPlaced::class,
        'listo-para-recoger' => OrderReadyForPickup::class,
        'enviado' => OrderShipped::class,
    ];

    public function __invoke(Request $request, Order $order, string $mail): Mailable
    {
        abort_unless($request->user()?->can('view', $order), 403);
        abort_unless(isset(self::MAILS[$mail]), 404);

        $locale = in_array($request->query('locale'), array_keys(config('app.available_locales')), true)
            ? $request->query('locale')
            : ($order->locale ?: config('app.locale'));
        app()->setLocale($locale);

        return (new (self::MAILS[$mail])($order))->locale($locale);
    }
}
