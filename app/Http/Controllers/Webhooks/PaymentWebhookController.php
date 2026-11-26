<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payments\Actions\CapturePayPalPayment;
use App\Domain\Payments\Actions\ConfirmPayment;
use App\Domain\Payments\Actions\FailPayment;
use App\Domain\Payments\Enums\Gateway;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Gateways\PayPal\PayPalGateway;
use App\Domain\Payments\Gateways\Redsys\RedsysGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Controllers\Controller;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Server-to-server payment answers. The ONLY places (with the PayPal capture) where an order becomes paid.
 * Both are handled synchronously and idempotently: shared hosting has no instant queue, and the
 * gateways retry when they get an error.
 */
class PaymentWebhookController extends Controller
{
    /** Redsys "notificación online" (DS_MERCHANT_MERCHANTURL). */
    public function redsys(Request $request, RedsysGateway $redsys, ConfirmPayment $confirm, FailPayment $fail): Response
    {
        $parameters = $redsys->verifiedNotification($request->only(['Ds_SignatureVersion', 'Ds_MerchantParameters', 'Ds_Signature']));

        if ($parameters === null) {
            Log::warning('Redsys notification with invalid signature', ['ip' => $request->ip()]);

            return response('Invalid signature', 400);
        }

        $payment = Payment::query()
            ->where('gateway', Gateway::Redsys)
            ->where('reference', (string) ($parameters['Ds_Order'] ?? ''))
            ->first();

        if (! $payment) {
            Log::warning('Redsys notification for unknown order', ['ds_order' => $parameters['Ds_Order'] ?? null]);

            return response('OK', 200);
        }

        // Only what we need: Redsys never sends the full card number, but keep the copy small.
        $data = array_intersect_key($parameters, array_flip([
            'Ds_Date', 'Ds_Hour', 'Ds_Amount', 'Ds_Currency', 'Ds_Order', 'Ds_Response', 'Ds_AuthorisationCode',
            'Ds_TransactionType', 'Ds_SecurePayment', 'Ds_Card_Country', 'Ds_Card_Brand', 'Ds_ProcessedPayMethod',
        ]));

        if (RedsysGateway::isAuthorised($parameters) && (string) ($parameters['Ds_Currency'] ?? '') === RedsysGateway::CURRENCY_EUR) {
            $confirm->handle($payment, (int) ($parameters['Ds_Amount'] ?? -1), (string) ($parameters['Ds_AuthorisationCode'] ?? ''), (string) $parameters['Ds_Response'], $data);
        } else {
            $fail->handle($payment, PaymentAttemptStatus::Failed, (string) ($parameters['Ds_Response'] ?? ''), self::redsysReason((string) ($parameters['Ds_Response'] ?? '')), $data);
        }

        return response('OK', 200);
    }

    /** PayPal webhooks: PAYMENT.CAPTURE.COMPLETED / DENIED and CHECKOUT.ORDER.APPROVED. */
    public function paypal(Request $request, PayPalGateway $paypal, ConfirmPayment $confirm, FailPayment $fail, CapturePayPalPayment $capture): Response
    {
        $event = (array) $request->json()->all();

        if (! $paypal->verifyWebhook($request->headers->all(), $event)) {
            Log::warning('PayPal webhook could not be verified', ['ip' => $request->ip(), 'type' => $event['event_type'] ?? null]);

            return response('Invalid signature', 400);
        }

        $resource = (array) ($event['resource'] ?? []);
        $type = (string) ($event['event_type'] ?? '');

        $payment = match ($type) {
            'CHECKOUT.ORDER.APPROVED' => Payment::query()->where('gateway', Gateway::PayPal)->where('reference', (string) ($resource['id'] ?? ''))->first(),
            default => Payment::query()->where('gateway', Gateway::PayPal)->where('ulid', (string) ($resource['custom_id'] ?? ''))->first(),
        };

        if (! $payment) {
            return response('OK', 200);
        }

        try {
            match ($type) {
                'CHECKOUT.ORDER.APPROVED' => $capture->handle($payment),
                'PAYMENT.CAPTURE.COMPLETED' => ($resource['amount']['currency_code'] ?? null) === 'EUR'
                    ? $confirm->handle($payment, (int) Money::toCents((string) ($resource['amount']['value'] ?? '-1')), (string) ($resource['id'] ?? ''), 'COMPLETED', [
                        'paypal_order_id' => $payment->reference, 'capture_id' => $resource['id'] ?? null, 'status' => 'COMPLETED',
                    ])
                    : null,
                'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' => $fail->handle($payment, PaymentAttemptStatus::Failed, 'DENIED', 'PayPal ha denegado el cobro.'),
                default => null,
            };
        } catch (DomainRuleException $e) {
            // Tell PayPal to retry later.
            Log::warning('PayPal webhook handling failed', ['type' => $type, 'error' => $e->getMessage()]);

            return response('Retry', 503);
        }

        return response('OK', 200);
    }

    /** Short Spanish reason for the most common Redsys refusal codes. */
    public static function redsysReason(string $code): string
    {
        return match ($code) {
            '0101' => 'Tarjeta caducada',
            '0102' => 'Tarjeta bloqueada temporalmente o en sospecha de fraude',
            '0116', '0190' => 'Saldo insuficiente o denegada por el banco',
            '0118' => 'Tarjeta no registrada',
            '0129' => 'CVV incorrecto',
            '0180', '0191' => 'Tarjeta no válida o caducada',
            '0184' => 'Fallo en la autenticación del titular (3-D Secure)',
            '9915' => 'El cliente canceló el pago',
            '9673', '9672' => 'Bizum: operación rechazada o cancelada',
            default => 'Pago no autorizado (código '.$code.')',
        };
    }
}
