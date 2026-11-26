<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Mail\OrderPlaced;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\StaffNotifier;
use App\Domain\Payments\Enums\PaymentAttemptStatus;
use App\Domain\Payments\Models\Payment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Records a payment the gateway has CONFIRMED server-to-server (Redsys notification, PayPal capture
 * or verified webhook). Safe to call twice: the second call changes nothing.
 * Never call it from the customer's return URL alone.
 */
class ConfirmPayment
{
    public function __construct(private readonly StaffNotifier $notifier) {}

    /** @param  array<string, mixed>  $gatewayData  Gateway answer without personal or card data. */
    public function handle(Payment $payment, int $amountCents, ?string $transactionId, ?string $responseCode, array $gatewayData = []): Payment
    {
        $result = DB::transaction(function () use ($payment, $amountCents, $transactionId, $responseCode, $gatewayData): ?string {
            /** @var Payment $locked */
            $locked = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($locked->status === PaymentAttemptStatus::Paid) {
                return null; // already recorded
            }

            if ($amountCents !== $locked->amount_cents) {
                $locked->update([
                    'status' => PaymentAttemptStatus::Failed,
                    'response_code' => $responseCode,
                    'failure_reason' => 'Importe distinto: '.Money::format($amountCents).' en lugar de '.Money::format($locked->amount_cents),
                    'gateway_data' => $gatewayData,
                    'failed_at' => now(),
                ]);
                Log::warning('Payment amount mismatch', ['payment' => $locked->ulid, 'expected' => $locked->amount_cents, 'got' => $amountCents]);

                return 'mismatch';
            }

            $locked->update([
                'status' => PaymentAttemptStatus::Paid,
                'transaction_id' => $transactionId,
                'response_code' => $responseCode,
                'gateway_data' => $gatewayData,
                'paid_at' => now(),
                'failure_reason' => null,
            ]);

            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($locked->order_id);

            if ($order->payment_status->isPaid()) {
                return 'double'; // a second attempt was also paid: the customer paid twice
            }

            $changes = ['payment_status' => PaymentStatus::Paid, 'paid_at' => now(), 'payment_expires_at' => null];

            if ($order->status === OrderStatus::PendingPayment) {
                $changes['status'] = OrderStatus::Confirmed;
            }

            $order->update($changes);

            return $order->status === OrderStatus::Cancelled ? 'cancelled' : 'paid';
        });

        $payment->refresh();
        $order = $payment->order()->first();

        match ($result) {
            'paid' => $this->afterPaid($order),
            'cancelled' => $this->notifier->attention($order, 'Pago recibido en un pedido cancelado: '.$order->number,
                'El cliente ha pagado '.Money::format($payment->amount_cents).' después de que el pedido caducara. Devuélvele el dinero o reactiva el pedido.'),
            'double' => $this->notifier->attention($order, 'Pago doble en el pedido '.$order->number,
                'El cliente ha pagado dos veces. Devuelve el segundo pago desde el pedido.'),
            'mismatch' => $this->notifier->attention($order, 'Importe incorrecto en el pedido '.$order->number,
                'La pasarela ha confirmado un importe distinto al del pedido. Revisa el pago antes de enviar nada.'),
            default => null,
        };

        return $payment;
    }

    private function afterPaid(Order $order): void
    {
        OrderPaid::dispatch($order);
        Mail::to($order->email)->locale($order->locale)->queue(new OrderPlaced($order));
        $this->notifier->newOrder($order);
    }
}
