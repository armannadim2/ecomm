<?php

namespace App\Domain\Content\Blocks;

/**
 * The block types of the page builder. Each type has a Blade view in
 * resources/views/store/blocks/{type}.blade.php and a form in App\Filament\Blocks\PageBlocks.
 */
final class BlockTypes
{
    public const SERVICE_STRIP = 'service_strip';

    public const HERO = 'hero';

    public const CAMPAIGN = 'campaign';

    public const CATEGORIES = 'categories';

    public const PRODUCTS = 'products';

    public const FEATURES = 'features';

    public const INFO_CARDS = 'info_cards';

    public const CTA = 'cta';

    public const IMAGE_BANNER = 'image_banner';

    public const RICH_TEXT = 'rich_text';

    public const TYPES = [
        self::SERVICE_STRIP, self::HERO, self::CAMPAIGN, self::CATEGORIES, self::PRODUCTS,
        self::FEATURES, self::INFO_CARDS, self::CTA, self::IMAGE_BANNER, self::RICH_TEXT,
    ];

    /** Icons staff can pick for cards (Heroicons, outline). */
    public const ICONS = [
        'building-storefront' => 'Tienda',
        'truck' => 'Envío',
        'credit-card' => 'Pago',
        'lock-closed' => 'Candado',
        'shield-check' => 'Garantía',
        'check-badge' => 'Sello de calidad',
        'map-pin' => 'Ubicación',
        'clock' => 'Horario',
        'bolt' => 'Rápido',
        'gift' => 'Regalo',
        'sparkles' => 'Novedad',
        'heart' => 'Favorito',
        'star' => 'Estrella',
        'chat-bubble-left-right' => 'Chat',
        'phone' => 'Teléfono',
        'envelope' => 'Correo',
        'arrow-path' => 'Devolución',
        'tag' => 'Oferta',
        'archive-box' => 'Caja',
        'shopping-bag' => 'Bolsa',
    ];

    public static function icon(?string $name): string
    {
        return 'heroicon-o-'.(array_key_exists((string) $name, self::ICONS) ? $name : 'sparkles');
    }
}
