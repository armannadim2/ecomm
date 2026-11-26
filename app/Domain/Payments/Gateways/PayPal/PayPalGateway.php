<?php

namespace App\Domain\Payments\Gateways\PayPal;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\PaymentRedirect;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Store\Settings\StoreSettings;
use App\Support\Money;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * PayPal Orders API v2 (developer.paypal.com/docs/api/orders/v2).
 * start() creates a PayPal order and sends the customer to approve it; when they come back,
 * capture() charges it server-side. Webhooks (PAYMENT.CAPTURE.*) are verified with PayPal itself.
 */
class PayPalGateway implements PaymentGateway
{
    public function __construct(
        private readonly PaymentSettings $settings,
        private readonly StoreSettings $store,
    ) {}

    public function baseUrl(): string
    {
        return $this->settings->paypal_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    }

    public function start(Payment $payment): PaymentRedirect
    {
        $order = $payment->order;

        $response = $this->call(fn (PendingRequest $http) => $http
            ->withHeaders(['PayPal-Request-Id' => 'start-'.$payment->ulid])
            ->post('/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->ulid,
                    'invoice_id' => $order->number.'-'.$payment->id,
                    'custom_id' => $payment->ulid,
                    'description' => Str::limit('Pedido '.$order->number, 120, ''),
                    'amount' => ['currency_code' => 'EUR', 'value' => self::amount($payment->amount_cents)],
                ]],
                'payment_source' => ['paypal' => ['experience_context' => [
                    'brand_name' => Str::limit($this->store->store_name, 120, ''),
                    'locale' => match ($order->locale) {
                        'ca' => 'ca-ES',
                        'en' => 'en-GB',
                        default => 'es-ES',
                    },
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('payment.paypal.return', $payment->ulid),
                    'cancel_url' => route('payment.paypal.cancel', $payment->ulid),
                ]]],
            ]));

        $approve = collect($response->json('links', []))->first(fn ($l) => in_array($l['rel'] ?? null, ['payer-action', 'approve'], true));

        if (! $response->json('id') || ! $approve) {
            throw new DomainRuleException('PayPal no ha devuelto el enlace de pago.');
        }

        $payment->update(['reference' => $response->json('id')]);

        return new PaymentRedirect($approve['href']);
    }

    /**
     * Captures an approved PayPal order. Returns ['status' => COMPLETED|DECLINED|..., 'capture_id', 'amount_cents', 'currency'].
     * Capturing twice is safe: PayPal answers ORDER_ALREADY_CAPTURED and we read the order instead.
     *
     * @return array{status: string, capture_id: ?string, amount_cents: ?int, currency: ?string}
     */
    public function capture(Payment $payment): array
    {
        $response = $this->call(fn (PendingRequest $http) => $http
            ->withHeaders(['PayPal-Request-Id' => 'capture-'.$payment->ulid])
            ->withBody('{}', 'application/json')
            ->post('/v2/checkout/orders/'.rawurlencode((string) $payment->reference).'/capture'), allowFailure: true);

        if ($response->failed()) {
            $issue = $response->json('details.0.issue');

            if ($issue === 'ORDER_ALREADY_CAPTURED') {
                return $this->orderStatus($payment);
            }

            if (in_array($issue, ['INSTRUMENT_DECLINED', 'PAYER_ACTION_REQUIRED'], true)) {
                return ['status' => 'DECLINED', 'capture_id' => null, 'amount_cents' => null, 'currency' => null];
            }

            throw new DomainRuleException('PayPal no ha podido cobrar el pedido'.($issue ? " ({$issue})" : '').'.');
        }

        return self::captureFrom((array) $response->json());
    }

    /** Current state of the PayPal order (reconciliation). @return array{status: string, capture_id: ?string, amount_cents: ?int, currency: ?string} */
    public function orderStatus(Payment $payment): array
    {
        $response = $this->call(fn (PendingRequest $http) => $http->get('/v2/checkout/orders/'.rawurlencode((string) $payment->reference)));

        $data = (array) $response->json();
        $result = self::captureFrom($data);

        // APPROVED = the customer approved but we never captured (e.g. closed the tab on the way back).
        if ($result['capture_id'] === null) {
            $result['status'] = (string) ($data['status'] ?? 'UNKNOWN');
        }

        return $result;
    }

    public function refund(Payment $payment, int $amountCents): string
    {
        if (! $payment->transaction_id) {
            throw new DomainRuleException('Este pago de PayPal no tiene cobro asociado: no se puede devolver.');
        }

        $response = $this->call(fn (PendingRequest $http) => $http
            ->withHeaders(['PayPal-Request-Id' => 'refund-'.$payment->ulid.'-'.$payment->refunded_cents.'-'.$amountCents])
            ->post('/v2/payments/captures/'.rawurlencode($payment->transaction_id).'/refund', [
                'amount' => ['currency_code' => 'EUR', 'value' => self::amount($amountCents)],
            ]));

        if (! in_array($response->json('status'), ['COMPLETED', 'PENDING'], true)) {
            throw new DomainRuleException('PayPal no ha confirmado la devolución.');
        }

        return (string) $response->json('id');
    }

    /** Asks PayPal whether a webhook call is genuine (POST /v1/notifications/verify-webhook-signature). */
    public function verifyWebhook(array $headers, array $event): bool
    {
        if (! $this->settings->paypalConfigured() || blank($this->settings->paypal_webhook_id)) {
            return false;
        }

        $header = fn (string $name) => $headers[strtolower($name)][0] ?? null;

        try {
            $response = $this->call(fn (PendingRequest $http) => $http->post('/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $header('PAYPAL-AUTH-ALGO'),
                'cert_url' => $header('PAYPAL-CERT-URL'),
                'transmission_id' => $header('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $header('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $header('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $this->settings->paypal_webhook_id,
                'webhook_event' => $event,
            ]));
        } catch (DomainRuleException) {
            return false;
        }

        return $response->json('verification_status') === 'SUCCESS';
    }

    /** PayPal amounts are decimal strings: 1234 cents → "12.34". */
    public static function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /** @return array{status: string, capture_id: ?string, amount_cents: ?int, currency: ?string} */
    public static function captureFrom(array $order): array
    {
        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;

        return [
            'status' => (string) ($capture['status'] ?? $order['status'] ?? 'UNKNOWN'),
            'capture_id' => $capture['id'] ?? null,
            'amount_cents' => isset($capture['amount']['value']) ? Money::toCents((string) $capture['amount']['value']) : null,
            'currency' => $capture['amount']['currency_code'] ?? null,
        ];
    }

    private function accessToken(): string
    {
        if (! $this->settings->paypalConfigured()) {
            throw new DomainRuleException('PayPal no está configurado: revisa Pedidos → Pagos online.');
        }

        // Only the token string is cached (never objects); keyed by client id + environment.
        $key = 'paypal.token.'.md5($this->settings->paypal_client_id.'|'.$this->baseUrl());

        if ($token = Cache::get($key)) {
            return $token;
        }

        try {
            $response = Http::asForm()->timeout(20)
                ->withBasicAuth((string) $this->settings->paypal_client_id, (string) $this->settings->paypal_client_secret)
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $e) {
            throw new DomainRuleException('No se ha podido conectar con PayPal. Inténtalo de nuevo en unos minutos.', previous: $e);
        }

        if ($response->failed() || ! $response->json('access_token')) {
            throw new DomainRuleException('PayPal ha rechazado las credenciales: revisa el Client ID y el secreto.');
        }

        $token = (string) $response->json('access_token');
        Cache::put($key, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 120)));

        return $token;
    }

    /** @param  callable(PendingRequest): Response  $request */
    private function call(callable $request, bool $allowFailure = false): Response
    {
        $http = Http::baseUrl($this->baseUrl())->withToken($this->accessToken())->acceptJson()->asJson()->timeout(30);

        try {
            $response = $request($http);
        } catch (ConnectionException $e) {
            throw new DomainRuleException('No se ha podido conectar con PayPal. Inténtalo de nuevo en unos minutos.', previous: $e);
        }

        if ($response->failed() && ! $allowFailure) {
            throw new DomainRuleException('PayPal ha devuelto un error: '.($response->json('message') ?? 'HTTP '.$response->status()));
        }

        return $response;
    }
}
