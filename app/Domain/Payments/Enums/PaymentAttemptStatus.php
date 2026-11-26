<?php

namespace App\Domain\Payments\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status of one payment attempt (the order's overall payment status is Orders\Enums\PaymentStatus). */
enum PaymentAttemptStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Esperando al banco',
            self::Paid => 'Pagado',
            self::Failed => 'Rechazado',
            self::Cancelled => 'Cancelado por el cliente',
            self::Expired => 'Caducado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled, self::Expired => 'gray',
        };
    }
}
