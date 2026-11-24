<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Order life cycle. Click & Collect: Confirmed → Preparing → ReadyForPickup → Completed.
 * Home delivery: Confirmed → Preparing → Shipped → Completed. PendingPayment is used by online payments.
 */
enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pendiente de pago',
            self::Confirmed => 'Confirmado',
            self::Preparing => 'En preparación',
            self::ReadyForPickup => 'Listo para recoger',
            self::Shipped => 'Enviado',
            self::Completed => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Confirmed, self::Preparing => 'info',
            self::ReadyForPickup, self::Shipped => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /** Statuses staff can move to from this one. */
    public function next(Fulfillment $fulfillment): array
    {
        return match ($this) {
            self::PendingPayment => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [$fulfillment === Fulfillment::Pickup ? self::ReadyForPickup : self::Shipped, self::Cancelled],
            self::ReadyForPickup, self::Shipped => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }
}
