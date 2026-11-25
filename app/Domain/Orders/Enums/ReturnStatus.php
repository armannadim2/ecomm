<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Life cycle of a return or exchange request:
 * Requested → Approved → Received → Refunded (return) or Exchanged (exchange). Staff can reject while it is Requested.
 */
enum ReturnStatus: string implements HasColor, HasLabel
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Refunded = 'refunded';
    case Exchanged = 'exchanged';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Solicitada',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
            self::Received => 'Producto recibido',
            self::Refunded => 'Reembolsada',
            self::Exchanged => 'Cambio entregado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved, self::Received => 'info',
            self::Rejected => 'danger',
            self::Refunded, self::Exchanged => 'success',
        };
    }

    /** @return list<self> Statuses staff can move to from this one. */
    public function next(ReturnType $type): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected],
            self::Approved => [self::Received],
            self::Received => [$type === ReturnType::Exchange ? self::Exchanged : self::Refunded],
            self::Rejected, self::Refunded, self::Exchanged => [],
        };
    }

    /** Still counts against the quantities that can be requested for an order. */
    public function isActive(): bool
    {
        return $this !== self::Rejected;
    }
}
