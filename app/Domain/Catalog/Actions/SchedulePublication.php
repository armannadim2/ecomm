<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Illuminate\Support\Carbon;

/**
 * Schedules a product to go live at a given moment (CAT-12). The ready-to-sell rules are checked
 * now, so staff find out today rather than on launch day. `catalog:publish-scheduled` (every
 * minute) publishes it; pass null to cancel the schedule.
 */
class SchedulePublication
{
    public function handle(Product $product, ?Carbon $at): Product
    {
        if ($at === null) {
            $product->forceFill(['scheduled_publish_at' => null])->save();

            return $product;
        }

        if ($product->isPublished()) {
            throw new DomainRuleException('El producto ya está publicado.');
        }

        if ($product->trashed()) {
            throw new DomainRuleException('Restaura el producto de la papelera antes de programarlo.');
        }

        if ($at->lte(now()->addMinute())) {
            throw new DomainRuleException('Elige una fecha y hora en el futuro (o publícalo ahora).');
        }

        PublishProduct::assertReadyToSell($product);

        $product->forceFill(['scheduled_publish_at' => $at->copy()->utc()])->save();

        return $product;
    }
}
