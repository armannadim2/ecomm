<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

/**
 * Publishes products whose scheduled moment has come (CAT-12). Runs every minute from the
 * scheduler. If a product is no longer ready to sell (e.g. its category was deleted), the
 * schedule is dropped and the person who uploaded it is told why.
 */
class PublishScheduledProducts extends Command
{
    protected $signature = 'catalog:publish-scheduled';

    protected $description = 'Publish products whose scheduled publication time has come';

    public function handle(PublishProduct $publish): int
    {
        $due = Product::query()
            ->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now())
            ->where('status', '!=', ProductStatus::Published)
            ->with('createdBy')
            ->get();

        foreach ($due as $product) {
            try {
                $publish->handle($product);
                $this->info("Publicado: {$product->name}");
            } catch (DomainRuleException $e) {
                $product->forceFill(['scheduled_publish_at' => null])->save();

                activity('catalog')->performedOn($product)
                    ->withProperties(['reason' => $e->getMessage()])
                    ->event('scheduled_publish_failed')
                    ->log('No se pudo publicar en la fecha programada');

                if ($product->createdBy) {
                    Notification::make()->danger()
                        ->title("«{$product->name}» no se ha podido publicar")
                        ->body($e->getMessage())
                        ->sendToDatabase($product->createdBy);
                }

                $this->warn("No publicado: {$product->name} — {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
