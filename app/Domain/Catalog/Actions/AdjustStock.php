<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

/**
 * The only way to change stock after a variant is created.
 * Uses a row lock so two simultaneous changes cannot overwrite each other,
 * and records the reason in the activity log.
 */
class AdjustStock
{
    public function handle(ProductVariant $variant, int $change, string $reason): ProductVariant
    {
        if ($change === 0) {
            throw new DomainRuleException('Indica una cantidad distinta de 0.');
        }

        if (trim($reason) === '') {
            throw new DomainRuleException('Indica el motivo del ajuste (por ejemplo: "Recepción de mercancía").');
        }

        return DB::transaction(function () use ($variant, $change, $reason): ProductVariant {
            /** @var ProductVariant $locked */
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->getKey());

            $before = $locked->stock;
            $after = $before + $change;

            if ($after < 0) {
                throw new DomainRuleException("No hay stock suficiente: quedan {$before} unidades.");
            }

            $locked->stock = $after;
            $locked->saveQuietly();

            activity('stock')
                ->performedOn($locked)
                ->withProperties(['before' => $before, 'change' => $change, 'after' => $after, 'reason' => $reason])
                ->event('stock_adjusted')
                ->log('Ajuste de stock');

            return $locked;
        });
    }
}
