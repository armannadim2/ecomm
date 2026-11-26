<?php

namespace App\Domain\Orders\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Failed => 'Fallido',
            self::PartiallyRefunded => 'Devuelto en parte',
            self::Refunded => 'Devuelto',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::PartiallyRefunded => 'info',
            self::Refunded => 'gray',
        };
    }

    /** Money was received (and not all of it returned yet). */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded], true);
    }
}
