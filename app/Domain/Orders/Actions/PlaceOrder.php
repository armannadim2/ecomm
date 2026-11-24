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
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Turns the cart into an order: checks the delivery and payment options, reserves stock
 * (with row locks), copies prices and contact data, empties the cart, e-mails the customer
 * and notifies the staff who handle orders.
 */
class PlaceOrder
{
    public const NUMBER_PREFIX = 'TM';

    public function __construct(
        private readonly Cart $cart,
        private readonly CheckoutSettings $settings,
        private readonly AdjustStock $adjustStock,
    ) {}

    /**
     * @param  array{fulfillment: string, payment_method: string, first_name: string, last_name: string, email: string, phone: string,
     *               tax_id?: ?string, ship_line1?: ?string, ship_line2?: ?string, ship_postal_code?: ?string, ship_city?: ?string,
     *               ship_province?: ?string, customer_note?: ?string, marketing?: bool}  $data
     */
    public function handle(array $data, ?Customer $customer = null): Order
    {
        if ($this->cart->isEmpty()) {
            throw new DomainRuleException(__('Tu cesta está vacía.'));
        }

        $fulfillment = Fulfillment::from($data['fulfillment']);
        $payment = PaymentMethod::from($data['payment_method']);
        $this->guardOptions($fulfillment, $payment);

        $order = DB::transaction(function () use ($data, $customer, $fulfillment, $payment): Order {
            $lines = $this->cart->lines();
            $this->reserveStock($lines);
            $totals = $this->cart->totals($fulfillment);

            $order = Order::query()->create([
                'number' => 'tmp-'.Str::ulid(),
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
                'tax_id' => $data['tax_id'] ?? null,
                'ship_line1' => $fulfillment === Fulfillment::Delivery ? $data['ship_line1'] ?? null : null,
                'ship_line2' => $fulfillment === Fulfillment::Delivery ? $data['ship_line2'] ?? null : null,
                'ship_postal_code' => $fulfillment === Fulfillment::Delivery ? $data['ship_postal_code'] ?? null : null,
                'ship_city' => $fulfillment === Fulfillment::Delivery ? $data['ship_city'] ?? null : null,
                'ship_province' => $fulfillment === Fulfillment::Delivery ? $data['ship_province'] ?? null : null,
                'subtotal_cents' => $totals->subtotal,
                'shipping_cents' => $totals->shipping,
                'total_cents' => $totals->total(),
                'tax_cents' => $totals->tax,
                'savings_cents' => $totals->savings,
                'locale' => app()->getLocale(),
                'marketing_opt_in' => (bool) ($data['marketing'] ?? false),
                'customer_note' => $data['customer_note'] ?? null,
                'placed_at' => now(),
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
        });

        $this->cart->clear();
        Mail::to($order->email)->locale($order->locale)->queue(new OrderPlaced($order));
        $this->notifyStaff($order);

        return $order;
    }

    private function guardOptions(Fulfillment $fulfillment, PaymentMethod $payment): void
    {
        if ($fulfillment === Fulfillment::Pickup && ! $this->settings->pickup_enabled
            || $fulfillment === Fulfillment::Delivery && ! $this->settings->delivery_enabled) {
            throw new DomainRuleException(__('Esta forma de entrega no está disponible.'));
        }

        if (! $payment->isAvailable()
            || $payment === PaymentMethod::PayInStore && (! $this->settings->pay_in_store_enabled || $fulfillment !== Fulfillment::Pickup)) {
            throw new DomainRuleException(__('Esta forma de pago no está disponible.'));
        }
    }

    /** Locks the variants and checks there is stock for every line before anything is written. */
    private function reserveStock($lines): void
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

    private function notifyStaff(Order $order): void
    {
        $permission = Permission::query()->where('name', 'orders.view')->where('guard_name', Staff::GUARD)->first();

        $staff = Staff::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q
                ->role(Staff::OWNER_ROLE)
                ->when($permission, fn ($q) => $q
                    ->orWhereHas('roles.permissions', fn ($p) => $p->whereKey($permission->id))
                    ->orWhereHas('permissions', fn ($p) => $p->whereKey($permission->id))))
            ->get();

        if ($staff->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Nuevo pedido '.$order->number)
            ->body($order->fullName().' · '.Money::format($order->total_cents).' · '.$order->fulfillment->getLabel())
            ->icon('heroicon-o-shopping-bag')
            ->actions([
                Action::make('open')->label('Ver pedido')->button()
                    ->url(OrderResource::getUrl('view', ['record' => $order], panel: 'admin')),
            ])
            ->sendToDatabase($staff);
    }
}
