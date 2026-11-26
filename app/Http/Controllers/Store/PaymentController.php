<?php

namespace App\Http\Controllers\Store;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\CapturePayPalPayment;
use App\Domain\Payments\Actions\FailPayment;
use App\Domain\Payments\Actions\StartPayment;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Customer side of online payments. The return pages NEVER mark an order as paid by themselves:
 * Redsys confirms through its server notification, PayPal through our own server-side capture.
 */
class PaymentController extends Controller
{
    /** Sends the customer to Redsys (auto-submitted form) or PayPal (redirect). */
    public function start(Request $request, Order $order, StartPayment $startPayment): View|RedirectResponse
    {
        abort_unless(CheckoutController::canSee($request, $order), 404);

        try {
            $redirect = $startPayment->handle($order);
        } catch (DomainRuleException $e) {
            return redirect()->to(self::orderUrl($order))->with('payment_error', $e->getMessage());
        }

        if (! $redirect->isPost()) {
            return redirect()->away($redirect->url);
        }

        return view('store.payment-redirect', ['redirect' => $redirect, 'order' => $order]);
    }

    /** Redsys sends the customer back here (GET or POST, depending on the terminal). */
    public function redsysReturn(string $payment, string $result): RedirectResponse
    {
        $payment = Payment::query()->where('ulid', $payment)->where('gateway', Gateway::Redsys)->with('order')->firstOrFail();

        return redirect()->to(self::orderUrl($payment->order))->with(
            $result === 'ok' ? 'payment_returned' : 'payment_error',
            $result === 'ok' ? true : __('El pago no se ha completado. Puedes intentarlo de nuevo o elegir otra forma de pago.'),
        );
    }

    /** PayPal: the customer approved the payment; we charge it server-side and check the answer. */
    public function paypalReturn(Request $request, string $payment, CapturePayPalPayment $capture): RedirectResponse
    {
        $payment = Payment::query()->where('ulid', $payment)->where('gateway', Gateway::PayPal)->with('order')->firstOrFail();

        if ($request->query('token') !== $payment->reference) {
            abort(404);
        }

        try {
            $paid = $capture->handle($payment);
        } catch (DomainRuleException $e) {
            return redirect()->to(self::orderUrl($payment->order))->with('payment_error', __('No hemos podido confirmar el pago con PayPal. Si se ha hecho el cargo, lo veremos en unos minutos; si no, vuelve a intentarlo.'));
        }

        return redirect()->to(self::orderUrl($payment->order))->with(
            $paid ? 'payment_returned' : 'payment_error',
            $paid ? true : __('PayPal no ha aceptado el pago. Puedes intentarlo de nuevo o elegir otra forma de pago.'),
        );
    }

    public function paypalCancel(string $payment, FailPayment $fail): RedirectResponse
    {
        $payment = Payment::query()->where('ulid', $payment)->where('gateway', Gateway::PayPal)->with('order')->firstOrFail();
        $fail->handle($payment, PaymentAttemptStatus::Cancelled, 'CANCELLED', 'El cliente volvió sin pagar.');

        return redirect()->to(self::orderUrl($payment->order))->with('payment_error', __('Has cancelado el pago. Tu pedido sigue reservado unos minutos por si quieres pagarlo.'));
    }

    /**
     * Coming back from the bank the session cookie may be missing (cross-site POST), so the
     * customer is sent to a signed link to their order instead.
     */
    public static function orderUrl(Order $order): string
    {
        return URL::temporarySignedRoute('order.show', now()->addDays(2), $order);
    }
}
