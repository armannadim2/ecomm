<?php

namespace App\Domain\Content\Blocks;

use App\Domain\Store\Settings\StoreSettings;

/**
 * One page-builder block (or one repeater item inside a block), read-only for the views.
 *
 * Translatable texts are stored as ['es' => ..., 'ca' => ..., 'en' => ...]; text() returns the
 * visitor's language and falls back to Spanish. Store placeholders such as [nombre_tienda]
 * are filled in. Links may use the special values [mapa], [whatsapp], [email] and [telefono].
 */
final class Block
{
    /** Extra data loaded for the block (products, categories...) by BlockRenderer. */
    public array $payload = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly string $key = '',
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

    public function has(string $key): bool
    {
        return $this->text($key) !== '';
    }

    /** Resolved link: normal URLs as typed, or a special value. Null when it cannot be resolved. */
    public function url(string $key): ?string
    {
        return self::resolveUrl((string) $this->get($key, ''));
    }

    /** @return list<Block> Repeater items as blocks, so they have text() and url() too. */
    public function items(string $key): array
    {
        return array_values(array_map(
            fn ($item) => new self($this->type.'.item', is_array($item) ? $item : []),
            (array) $this->get($key, []),
        ));
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
