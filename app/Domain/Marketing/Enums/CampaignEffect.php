<?php

namespace App\Domain\Marketing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Visual effect drawn over the home page while a campaign is live.
 * Implemented in resources/js/campaign-effects.js (respects "reduce motion").
 */
enum CampaignEffect: string implements HasLabel
{
    case None = 'none';
    case Snow = 'snow';
    case Confetti = 'confetti';
    case Hearts = 'hearts';
    case Leaves = 'leaves';
    case Sparkles = 'sparkles';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'Sin efecto',
            self::Snow => 'Nieve',
            self::Confetti => 'Confeti',
            self::Hearts => 'Corazones',
            self::Leaves => 'Hojas de otoño',
            self::Sparkles => 'Destellos',
        };
    }
}
