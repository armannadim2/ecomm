<?php

namespace App\Domain\Payments\Gateways\Redsys;

use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentRedirect;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Redsys redirect integration ("Redirección") for cards (3-D Secure) and Bizum.
 * The customer's browser POSTs the signed form to Redsys; Redsys calls our notification URL
 * (api/webhooks/redsys) server-to-server with the result. Refunds use the Redsys REST API.
 */
class RedsysGateway implements PaymentGateway
{
    public const CURRENCY_EUR = '978';

    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly StoreSettings $store,
    ) {}

    public function paymentUrl(): string
    {
        return $this->settings->redsys_test_mode
            ? 'https://sis-t.redsys.es:25443/sis/realizarPago'
            : 'https://sis.redsys.es/sis/realizarPago';
    }

    public function restUrl(): string
    {
        return $this->settings->redsys_test_mode
            ? 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST'
            : 'https://sis.redsys.es/sis/rest/trataPeticionREST';
    }

    public function start(Payment $payment): PaymentRedirect
    {
        $this->ensureConfigured();
        $order = $payment->order;

        $parameters = [
            'DS_MERCHANT_AMOUNT' => (string) $payment->amount_cents,
            'DS_MERCHANT_ORDER' => $payment->reference,
            'DS_MERCHANT_MERCHANTCODE' => (string) $this->settings->redsys_merchant_code,
            'DS_MERCHANT_TERMINAL' => $this->settings->redsys_terminal,
            'DS_MERCHANT_CURRENCY' => self::CURRENCY_EUR,
            'DS_MERCHANT_TRANSACTIONTYPE' => '0', // authorisation (immediate charge)
            'DS_MERCHANT_MERCHANTURL' => route('webhooks.redsys'),
            'DS_MERCHANT_URLOK' => route('payment.redsys.return', [$payment->ulid, 'ok']),
            'DS_MERCHANT_URLKO' => route('payment.redsys.return', [$payment->ulid, 'ko']),
            'DS_MERCHANT_CONSUMERLANGUAGE' => match ($order->locale) {
                'ca' => '003',
                'en' => '002',
                default => '001',
            },
            'DS_MERCHANT_PRODUCTDESCRIPTION' => Str::limit('Pedido '.$order->number, 120, ''),
            'DS_MERCHANT_MERCHANTNAME' => Str::limit($this->store->store_name, 25, ''),
            'DS_MERCHANT_TITULAR' => Str::limit($order->fullName(), 60, ''),
            // z = Bizum, C = card only (otherwise Redsys shows every method the terminal has).
            'DS_MERCHANT_PAYMETHODS' => $payment->method === PaymentMethod::Bizum ? 'z' : 'C',
        ];

        $encoded = RedsysSignature::encodeParameters($parameters);

        return new PaymentRedirect($this->paymentUrl(), 'POST', [
            'Ds_SignatureVersion' => RedsysSignature::VERSION,
            'Ds_MerchantParameters' => $encoded,
            'Ds_Signature' => RedsysSignature::sign($encoded, (string) $payment->reference, (string) $this->settings->redsys_secret_key),
        ]);
    }

    /**
     * Checks a notification from Redsys and returns its decoded parameters, or null when the
     * signature, merchant code or terminal do not match (never trust an unsigned answer).
     *
     * @param  array<string, mixed>  $input  Ds_SignatureVersion, Ds_MerchantParameters, Ds_Signature
     * @return array<string, mixed>|null
     */
    public function verifiedNotification(array $input): ?array
    {
        if (! $this->settings->redsysConfigured()) {
            return null;
        }

        $encoded = (string) ($input['Ds_MerchantParameters'] ?? '');
        $signature = (string) ($input['Ds_Signature'] ?? '');
        $parameters = RedsysSignature::decodeParameters($encoded);
        $order = (string) ($parameters['Ds_Order'] ?? $parameters['DS_ORDER'] ?? '');

        if ($encoded === '' || $signature === '' || $order === '') {
            return null;
        }

        $expected = RedsysSignature::sign($encoded, $order, (string) $this->settings->redsys_secret_key);

        if (! RedsysSignature::matches($expected, $signature)) {
            return null;
        }

        if (ltrim((string) ($parameters['Ds_MerchantCode'] ?? ''), '0') !== ltrim((string) $this->settings->redsys_merchant_code, '0')
            || (int) ($parameters['Ds_Terminal'] ?? 0) !== (int) $this->settings->redsys_terminal) {
            return null;
        }

        return $parameters;
    }

    /** Ds_Response 0000–0099 = authorised. Everything else (incl. 9xxx errors) = not paid. */
    public static function isAuthorised(array $parameters): bool
    {
        $code = $parameters['Ds_Response'] ?? null;

        return is_numeric($code) && (int) $code >= 0 && (int) $code <= 99;
    }

    public function refund(Payment $payment, int $amountCents): string
    {
        $this->ensureConfigured();

        $parameters = [
            'DS_MERCHANT_AMOUNT' => (string) $amountCents,
            'DS_MERCHANT_ORDER' => $payment->reference,
            'DS_MERCHANT_MERCHANTCODE' => (string) $this->settings->redsys_merchant_code,
            'DS_MERCHANT_TERMINAL' => $this->settings->redsys_terminal,
            'DS_MERCHANT_CURRENCY' => self::CURRENCY_EUR,
            'DS_MERCHANT_TRANSACTIONTYPE' => '3', // devolución automática
        ];
        $encoded = RedsysSignature::encodeParameters($parameters);

        try {
            $response = Http::timeout(30)->acceptJson()->asJson()->post($this->restUrl(), [
                'Ds_SignatureVersion' => RedsysSignature::VERSION,
                'Ds_MerchantParameters' => $encoded,
                'Ds_Signature' => RedsysSignature::sign($encoded, (string) $payment->reference, (string) $this->settings->redsys_secret_key),
            ]);
        } catch (ConnectionException $e) {
            throw new DomainRuleException('No se ha podido conectar con Redsys. Inténtalo de nuevo en unos minutos.', previous: $e);
        }

        if ($response->json('errorCode')) {
            throw new DomainRuleException('Redsys no ha aceptado la devolución (código '.$response->json('errorCode').').');
        }

        $answer = $this->verifiedNotification((array) $response->json());

        // 0900 = refund accepted.
        if ($answer === null || (string) ($answer['Ds_Response'] ?? '') !== '0900') {
            throw new DomainRuleException('Redsys no ha confirmado la devolución'.(isset($answer['Ds_Response']) ? ' (respuesta '.$answer['Ds_Response'].')' : '').'.');
        }

        return (string) ($answer['Ds_AuthorisationCode'] ?? $payment->reference);
    }

    /** Ds_Order: 12 digits (Redsys asks for 4–12 characters starting with 4 digits, unique per terminal). */
    public static function newReference(int $paymentId): string
    {
        return str_pad((string) $paymentId, 9, '0', STR_PAD_LEFT).random_int(100, 999);
    }

    private function ensureConfigured(): void
    {
        if (! $this->settings->redsysConfigured()) {
            throw new DomainRuleException('Redsys no está configurado: revisa Pedidos → Pagos online.');
        }
    }
}
