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

    /** Admin names, short descriptions and icons (section library of the visual editor). */
    public const META = [
        self::SERVICE_STRIP => ['Franja de servicio', 'Mensaje corto y categorías destacadas', 'megaphone'],
        self::HERO => ['Portada (hero)', 'Título grande, botones, cifras y fotos', 'sparkles'],
        self::CAMPAIGN => ['Campaña activa', 'Banner de la campaña en curso', 'gift'],
        self::CATEGORIES => ['Departamentos', 'Tarjetas de las categorías', 'squares-2x2'],
        self::PRODUCTS => ['Productos', 'Novedades, una categoría u ofertas', 'shopping-bag'],
        self::FEATURES => ['Banner de ventajas', 'Título, texto y 3 tarjetas con icono', 'check-badge'],
        self::INFO_CARDS => ['Tarjetas informativas', 'Hasta 3 tarjetas grandes con recuadro', 'information-circle'],
        self::CTA => ['Llamada a la acción', 'Banner centrado con dos botones', 'cursor-arrow-rays'],
        self::IMAGE_BANNER => ['Banner con imagen', 'Foto grande con título y botón', 'photo'],
        self::RICH_TEXT => ['Texto libre', 'Títulos, párrafos, listas y enlaces', 'document-text'],
    ];

    public static function label(string $type): string
    {
        return self::META[$type][0] ?? $type;
    }

    /**
     * Starting content for a section added in the visual editor: the Stitch content of the
     * home page when that type exists there, otherwise a short placeholder in three languages.
     *
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        foreach (DefaultContent::home() as $block) {
            if ($block['type'] === $type) {
                return $block['data'];
            }
        }

        $t = fn (string $es, string $ca, string $en) => ['es' => $es, 'ca' => $ca, 'en' => $en];

        return match ($type) {
            self::IMAGE_BANNER => [
                'title' => $t('Título del banner', 'Títol del bàner', 'Banner title'),
                'text' => $t('Escribe aquí un texto corto.', 'Escriu aquí un text curt.', 'Write a short text here.'),
                'button_label' => $t('Ver más', 'Veure més', 'See more'),
                'button_url' => '#novedades',
            ],
            self::RICH_TEXT => [
                'title' => $t('Título', 'Títol', 'Title'),
                'body' => $t('<p>Escribe aquí tu texto.</p>', '<p>Escriu aquí el teu text.</p>', '<p>Write your text here.</p>'),
                'width' => 'narrow',
            ],
            default => [],
        };
    }

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
