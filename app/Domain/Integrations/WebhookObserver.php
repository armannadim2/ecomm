<?php

namespace App\Domain\Integrations;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Orders\Models\Order;
use App\Http\Resources\Api\V1\OrderResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\VariantResource;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Turns catalog and order changes (from the admin, the shop or the API) into webhook events.
 * Runs after the database commit so a new order already has its items.
 */
class WebhookObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly WebhookDispatcher $webhooks) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Order => $this->send(WebhookEvents::ORDER_CREATED, $model),
            $model instanceof Product => $this->send(WebhookEvents::PRODUCT_CREATED, $model),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        $changed = array_diff(array_keys($model->getChanges()), ['updated_at']);
        if ($changed === []) {
            return;
        }

        match (true) {
            $model instanceof Order => $this->send(WebhookEvents::ORDER_UPDATED, $model),
            $model instanceof Product => $this->send(WebhookEvents::PRODUCT_UPDATED, $model),
            $model instanceof ProductVariant && in_array('stock', $changed, true) => $this->send(WebhookEvents::STOCK_UPDATED, $model),
            $model instanceof ProductVariant => $model->product && $this->send(WebhookEvents::PRODUCT_UPDATED, $model->product),
            default => null,
        };
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof Product) {
            $this->send(WebhookEvents::PRODUCT_DELETED, $model);
        }
    }

    /** Called by AdjustStock, which saves without model events. */
    public function stockChanged(ProductVariant $variant): void
    {
        $this->send(WebhookEvents::STOCK_UPDATED, $variant);
    }

    private function send(string $event, Model $model): bool
    {
        if (! $this->webhooks->hasListeners($event)) {
            return false;
        }

        $data = match (true) {
            $model instanceof Order => (new OrderResource($model->loadMissing('items.product', 'items.variant', 'customer')))->resolve(),
            $model instanceof Product => (new ProductResource($model->loadMissing('brand', 'categories', 'variants', 'media')))->resolve(),
            $model instanceof ProductVariant => (new VariantResource($model->loadMissing('product')))->resolve(),
        };

        $this->webhooks->dispatch($event, $data);

        return true;
    }
}
