<?php

namespace App\Domain\Content\Blocks;

use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Support\HtmlString;

/**
 * One page-builder block (or one repeater item inside a block), read-only for the views.
 *
 * Translatable texts are stored as ['es' => ..., 'ca' => ..., 'en' => ...]; text() returns the
 * visitor's language and falls back to Spanish. Store placeholders such as [nombre_tienda]
 * are filled in. Links may use the special values [mapa], [whatsapp], [email] and [telefono].
 *
 * In the visual editor preview, editable() wraps texts in a marker the editor script turns
 * into click-to-type fields; `$path` tells the editor where the text lives ("3.cards.0.title").
 */
final class Block
{
    /** Extra data loaded for the block (products, categories...) by BlockRenderer. */
    public array $payload = [];

    /** Position of the block in the page (visual editor). */
    public int $index = 0;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly string $key = '',
        public readonly string $path = '',
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    /** Text in the current language (Spanish fallback), placeholders filled in. Escape it in Blade. */
    public function text(string $key, array $replace = []): string
    {
        $value = $this->get($key);

        if (is_array($value)) {
            $locale = app()->getLocale();
            $value = filled($value[$locale] ?? null) ? $value[$locale] : ($value['es'] ?? '');
        }

        return strtr((string) $value, $replace + self::storeReplacements());
    }

    /**
     * Escaped text for element content. In the visual editor it becomes click-to-edit,
     * except texts with placeholders ([nombre_tienda]...), which are edited in the side panel.
     */
    public function editable(string $key, array $replace = []): HtmlString
    {
        $text = $this->text($key, $replace);

        if (! PageEditing::active()) {
            return new HtmlString(e($text));
        }

        $raw = $this->get($key);
        $source = is_array($raw) ? (string) ($raw[app()->getLocale()] ?? '') : (string) $raw;
        $fallback = is_array($raw) ? (string) ($raw['es'] ?? '') : '';

        if (preg_match('/\[[a-zñ_]+\]/u', $source.$fallback) || $replace !== []) {
            return new HtmlString(e($text));
        }

        return new HtmlString(sprintf(
            '<span data-pb-edit="%s" data-pb-translatable="%s" data-pb-placeholder="%s">%s</span>',
            e($this->path.$key),
            is_array($raw) || $raw === null ? '1' : '0',
            e(__('Escribe aquí…')),
            e($text),
        ));
    }

    public function has(string $key): bool
    {
        return $this->text($key) !== '' || (PageEditing::active() && $this->get($key) !== null);
    }

    /** Resolved link: normal URLs as typed, or a special value. Null when it cannot be resolved. */
    public function url(string $key): ?string
    {
        return self::resolveUrl((string) $this->get($key, ''));
    }

    /** @return list<Block> Repeater items as blocks, so they have text() and url() too. */
    public function items(string $key): array
    {
        $items = [];

        foreach (array_values((array) $this->get($key, [])) as $i => $item) {
            $items[] = new self($this->type.'.item', is_array($item) ? $item : [], '', $this->path.$key.'.'.$i.'.');
        }

        return $items;
    }

    /** Wrapper classes from the block's "Diseño" options (background, spacing, visibility). */
    public function wrapperClasses(): string
    {
        $style = (array) $this->get('style', []);

        return implode(' ', array_filter([
            match ($style['background'] ?? 'none') {
                'white' => 'bg-white',
                'cream' => 'bg-surface-container-low',
                'peach' => 'bg-secondary-container/60',
                default => null,
            },
            match ($style['spacing_top'] ?? 'normal') {
                'none' => '-mt-4',
                'large' => 'pt-10',
                default => null,
            },
            match ($style['spacing_bottom'] ?? 'normal') {
                'none' => '-mb-4',
                'large' => 'pb-10',
                default => null,
            },
            match ($style['visibility'] ?? 'all') {
                'desktop' => 'hidden md:block',
                'mobile' => 'md:hidden',
                default => null,
            },
        ]));
    }

    public static function resolveUrl(string $url): ?string
    {
        $store = app(StoreSettings::class);
        $url = trim($url);

        return match ($url) {
            '' => null,
            '[mapa]' => $store->address_line ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($store->fullAddress()) : null,
            '[whatsapp]' => $store->phone ? 'https://wa.me/'.preg_replace('/\D/', '', str_starts_with($store->phone, '+') ? $store->phone : '34'.$store->phone) : null,
            '[email]' => $store->email ? 'mailto:'.$store->email : null,
            '[telefono]' => $store->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $store->phone) : null,
            default => preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $url) ? $url : null,
        };
    }

    public static function isExternal(?string $url): bool
    {
        return $url !== null && str_starts_with($url, 'http') && ! str_starts_with($url, url('/'));
    }

    /** @return array<string, string> */
    private static function storeReplacements(): array
    {
        $store = app(StoreSettings::class);

        return [
            '[nombre_tienda]' => (string) $store->store_name,
            '[ciudad]' => (string) $store->city,
        ];
    }
}
