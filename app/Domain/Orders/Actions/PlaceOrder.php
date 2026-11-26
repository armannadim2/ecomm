<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Cart\Cart;
use App\Domain\Orders\Cart\CartLine;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Enums\ShippingZone;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Orders\StaffNotifier;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Turns the cart into an order: checks the delivery and payment options, reserves stock
 * (with row locks), copies prices and contact data and empties the cart.
 *
 * Pay-in-store orders are confirmed at once (e-mail + staff notification). Online orders wait in
 * "pending payment" with their stock reserved for PaymentSettings::$reservation_minutes; the e-mail
 * and the staff notification go out when the gateway confirms the payment (ConfirmPayment).
 * The same checkout_token (hidden form field) always gives back the same order: double clicks are harmless.
 */
class PlaceOrder
{
    public const NUMBER_PREFIX = 'TM';

    public function __construct(
        private readonly Cart $cart,
        private readonly CheckoutSettings $settings,
        private readonly AdjustStock $adjustStock,
        private readonly PaymentSettings $payments,
        private readonly StaffNotifier $notifier,
    ) {}

    /**
     * @param  array{fulfillment: string, payment_method: string, first_name: string, last_name: string, email: string, phone: string,
     *               tax_id?: ?string, ship_line1?: ?string, ship_line2?: ?string, ship_postal_code?: ?string, ship_city?: ?string,
     *               ship_province?: ?string, customer_note?: ?string, marketing?: bool, checkout_token?: ?string, accept_terms?: bool}  $data
     */
    public function handle(array $data, ?Customer $customer = null): Order
    {
        $key = filled($data['checkout_token'] ?? null) ? (string) $data['checkout_token'] : null;

        if ($key && ($existing = Order::query()->where('idempotency_key', $key)->first())) {
            return $existing;
        }

        if ($this->cart->isEmpty()) {
            throw new DomainRuleException(__('Tu cesta está vacía.'));
        }

        $fulfillment = Fulfillment::from($data['fulfillment']);
        $payment = PaymentMethod::from($data['payment_method']);
        $this->guardOptions($fulfillment, $payment);

        $zone = null;
        if ($fulfillment === Fulfillment::Delivery) {
            $zone = ShippingZone::fromPostalCode($data['ship_postal_code'] ?? null, $this->settings)
                ?? throw new DomainRuleException(__('De momento solo enviamos a la Península y Baleares.'));
        }

        try {
            $order = DB::transaction(fn (): Order => $this->create($data, $customer, $fulfillment, $payment, $zone, $key));
        } catch (UniqueConstraintViolationException $e) {
            // The same form was submitted twice at the same moment: return the order the first one made.
            if ($key && ($existing = Order::query()->where('idempotency_key', $key)->first())) {
                return $existing;
            }

            throw $e;
        }

        $this->cart->clear();

        if (! $payment->isOnline()) {
            Mail::to($order->email)->locale($order->locale)->queue(new OrderPlaced($order));
            $this->notifier->newOrder($order);
        }

        return $order;
    }

    private function create(array $data, ?Customer $customer, Fulfillment $fulfillment, PaymentMethod $payment, ?ShippingZone $zone, ?string $key): Order
    {
        $lines = $this->cart->lines();
        $this->reserveStock($lines);
        $totals = $this->cart->totals($fulfillment, $data['ship_postal_code'] ?? null);
        $delivery = $fulfillment === Fulfillment::Delivery;

        $order = Order::query()->create([
            'number' => 'tmp-'.Str::ulid(),
            'idempotency_key' => $key,
            'customer_id' => $customer?->id,
            'status' => $payment->isOnline() ? OrderStatus::PendingPayment : OrderStatus::Confirmed,
            'fulfillment' => $fulfillment,
            'payment_method' => $payment,
            'payment_status' => PaymentStatus::Pending,
            'pickup_code' => $fulfillment === Fulfillment::Pickup ? $this->pickupCode() : null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'tax_id' => filled($data['tax_id'] ?? null) ? mb_strtoupper(str_replace(['-', ' '], '', $data['tax_id'])) : null,
            'ship_line1' => $delivery ? $data['ship_line1'] ?? null : null,
            'ship_line2' => $delivery ? $data['ship_line2'] ?? null : null,
            'ship_postal_code' => $delivery ? $data['ship_postal_code'] ?? null : null,
            'ship_city' => $delivery ? $data['ship_city'] ?? null : null,
            'ship_province' => $delivery ? $data['ship_province'] ?? null : null,
            'shipping_zone' => $zone?->value,
            'subtotal_cents' => $totals->subtotal,
            'shipping_cents' => $totals->shipping,
            'total_cents' => $totals->total(),
            'tax_cents' => $totals->tax,
            'savings_cents' => $totals->savings,
            'locale' => app()->getLocale(),
            'marketing_opt_in' => (bool) ($data['marketing'] ?? false),
            'customer_note' => $data['customer_note'] ?? null,
            'placed_at' => now(),
            'terms_accepted_at' => ! empty($data['accept_terms']) ? now() : null,
            'payment_expires_at' => $payment->isOnline() ? now()->addMinutes(max(5, $this->payments->reservation_minutes)) : null,
        ]);

        $order->update(['number' => sprintf('%s-%s-%05d', self::NUMBER_PREFIX, now()->year, $order->id)]);

        foreach ($lines as $line) {
            /** @var CartLine $line */
            $order->items()->create([
                'product_id' => $line->product->id,
                'product_variant_id' => $line->variant->id,
                'product_name' => $line->product->name,
                'variant_name' => $line->variant->name,
                'sku' => $line->variant->sku,
                'unit_price_cents' => $line->unitPrice(),
                'compare_at_price_cents' => $line->compareAt(),
                'quantity' => $line->quantity,
                'tax_rate' => $line->taxRate(),
                'line_total_cents' => $line->total(),
            ]);

            if ($line->variant->track_stock) {
                $this->adjustStock->handle($line->variant, -$line->quantity, 'Pedido '.$order->number);
            }
        }

        return $order;
    }

    private function guardOptions(Fulfillment $fulfillment, PaymentMethod $payment): void
    {
        // Home delivery is paid online, so it needs at least one gateway switched on.
        if ($fulfillment === Fulfillment::Pickup && ! $this->settings->pickup_enabled
            || $fulfillment === Fulfillment::Delivery && (! $this->settings->delivery_enabled || ! $this->payments->anyOnlineReady())) {
            throw new DomainRuleException(__('Esta forma de entrega no está disponible.'));
        }

        if (! $payment->isAvailable()
            || $payment === PaymentMethod::PayInStore && (! $this->settings->pay_in_store_enabled || $fulfillment !== Fulfillment::Pickup)) {
            throw new DomainRuleException(__('Esta forma de pago no está disponible.'));
        }
    }

    /** Locks the variants and checks there is stock for every line before anything is written. */
    private function reserveStock(Collection $lines): void
    {
        $locked = ProductVariant::query()
            ->whereIn('id', $lines->map(fn (CartLine $l) => $l->variant->id))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($lines as $line) {
            $variant = $locked->get($line->variant->id);

            if ($variant && $variant->track_stock && $variant->stock < $line->quantity) {
                throw new DomainRuleException(__('Solo quedan :count unidades de «:product». Ajusta la cantidad en tu cesta.', [
                    'count' => max(0, $variant->stock),
                    'product' => $line->product->name,
                ]));
            }
        }
    }

    /** Short code for the counter, without easily confused characters (0/O, 1/I). */
    private function pickupCode(): string
    {
        do {
            $code = self::NUMBER_PREFIX.'-'.collect(range(1, 6))->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])->implode('');
        } while (Order::query()->where('pickup_code', $code)->exists());

        return $code;
    }
}
