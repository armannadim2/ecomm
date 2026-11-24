<?php

namespace App\Domain\Marketing\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Ready-made campaign presets for the Spanish retail calendar. Choosing one
 * pre-fills effect, colour and headline; staff can change anything after.
 */
enum CampaignTheme: string implements HasLabel
{
    case Rebajas = 'rebajas';
    case SanValentin = 'san_valentin';
    case DiaDeLaMadre = 'dia_madre';
    case Verano = 'verano';
    case VueltaAlCole = 'vuelta_cole';
    case Halloween = 'halloween';
    case BlackFriday = 'black_friday';
    case CyberMonday = 'cyber_monday';
    case Navidad = 'navidad';
    case Personalizada = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rebajas => 'Rebajas',
            self::SanValentin => 'San Valentín',
            self::DiaDeLaMadre => 'Día de la Madre',
            self::Verano => 'Verano',
            self::VueltaAlCole => 'Vuelta al cole',
            self::Halloween => 'Halloween',
            self::BlackFriday => 'Black Friday',
            self::CyberMonday => 'Cyber Monday',
            self::Navidad => 'Navidad',
            self::Personalizada => 'Personalizada',
        };
    }

    /** @return array{effect: CampaignEffect, accent_color: string, headline: string, subheadline: string, cta_label: string} */
    public function defaults(): array
    {
        $d = match ($this) {
            self::Rebajas => [CampaignEffect::Confetti, '#dc2626', 'Rebajas', 'Hasta un 50 % en una selección de productos', 'Ver rebajas'],
            self::SanValentin => [CampaignEffect::Hearts, '#e11d48', 'San Valentín', 'Regalos para quien más quieres', 'Ver regalos'],
            self::DiaDeLaMadre => [CampaignEffect::Hearts, '#db2777', 'Día de la Madre', 'Ideas de regalo para mamá', 'Ver regalos'],
            self::Verano => [CampaignEffect::Sparkles, '#f59e0b', 'Verano', 'Todo lo que necesitas para el buen tiempo', 'Descubrir'],
            self::VueltaAlCole => [CampaignEffect::None, '#2563eb', 'Vuelta al cole', 'Prepárate para septiembre', 'Ver productos'],
            self::Halloween => [CampaignEffect::Leaves, '#ea580c', 'Halloween', 'Ofertas de miedo', 'Ver ofertas'],
            self::BlackFriday => [CampaignEffect::Sparkles, '#000000', 'Black Friday', 'Los mejores descuentos del año', 'Ver ofertas'],
            self::CyberMonday => [CampaignEffect::Sparkles, '#7c3aed', 'Cyber Monday', 'Último día de descuentos', 'Aprovechar'],
            self::Navidad => [CampaignEffect::Snow, '#15803d', 'Navidad', 'Regalos para todos, con envío a tiempo', 'Ver regalos'],
            self::Personalizada => [CampaignEffect::None, '#111827', '', '', ''],
        };

        return [
            'effect' => $d[0], 'accent_color' => $d[1], 'headline' => $d[2], 'subheadline' => $d[3], 'cta_label' => $d[4],
        ];
    }
}
