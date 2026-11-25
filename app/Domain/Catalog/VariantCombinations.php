<?php

namespace App\Domain\Catalog;

use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Str;

/**
 * Variant builder (CAT-09): every combination of up to two option lists, e.g.
 * sizes [S, M] × colours [Azul, Rojo] → "S / Azul", "S / Rojo", "M / Azul", "M / Rojo",
 * each with a unique SKU like "CAMISETA-S-AZUL".
 */
final class VariantCombinations
{
    public const MAX_VARIANTS = 100;

    /**
     * @param  list<string>  $first  e.g. sizes
     * @param  list<string>  $second  e.g. colours (may be empty)
     * @param  list<string>  $existingNames  variant names already on the product (skipped)
     * @return list<array{name: string, sku: string}>
     */
    public static function generate(array $first, array $second, string $skuPrefix, array $existingNames = []): array
    {
        $first = self::clean($first);
        $second = self::clean($second);

        if ($first === [] && $second === []) {
            throw new DomainRuleException('Escribe al menos un valor (por ejemplo, las tallas).');
        }

        $names = match (true) {
            $first === [] => $second,
            $second === [] => $first,
            default => array_merge(...array_map(fn ($a) => array_map(fn ($b) => "{$a} / {$b}", $second), $first)),
        };

        if (count($names) > self::MAX_VARIANTS) {
            throw new DomainRuleException('Salen '.count($names).' combinaciones: el máximo es '.self::MAX_VARIANTS.'.');
        }

        $existing = array_map(fn ($n) => mb_strtolower(trim((string) $n)), $existingNames);
        $prefix = Str::upper(Str::slug($skuPrefix)) ?: 'VAR';
        $taken = [];
        $variants = [];

        foreach ($names as $name) {
            if (in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }

            $base = Str::limit($prefix.'-'.Str::upper(Str::slug(str_replace('/', ' ', $name))), 58, '');
            $sku = $base;
            for ($i = 2; in_array($sku, $taken, true) || ProductVariant::query()->where('sku', $sku)->exists(); $i++) {
                $sku = $base.'-'.$i;
            }
            $taken[] = $sku;

            $variants[] = ['name' => $name, 'sku' => $sku];
        }

        return $variants;
    }

    /** @param  list<string>  $values  @return list<string> */
    private static function clean(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($v) => Str::limit(trim(preg_replace('/\s+/', ' ', (string) $v)), 40, ''),
            $values,
        ), fn ($v) => $v !== '')));
    }
}
