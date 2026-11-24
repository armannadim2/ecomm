<?php

namespace App\Domain\Catalog\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::PendingReview => 'Pendiente de revisión',
            self::Published => 'Publicado',
            self::Archived => 'Archivado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingReview => 'info',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }
}
