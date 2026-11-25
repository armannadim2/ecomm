<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'tax_class' => $this->tax_class?->value,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? ['id' => $this->brand->id, 'name' => $this->brand->name, 'slug' => $this->brand->slug] : null),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => ['id' => $c->ulid, 'name' => $c->name, 'slug' => $c->slug])->values()),
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
            'images' => $this->whenLoaded('media', fn () => $this->getMedia(Product::IMAGES)->map(fn (Media $m) => [
                'id' => $m->uuid,
                'url' => $m->getUrl(),
                'thumb_url' => $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : null,
                'alt' => $m->getCustomProperty('alt'),
            ])->values()),
            'url' => $this->status === ProductStatus::Published ? route('store.product', $this->slug) : null,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'published_at' => $this->published_at?->toIso8601String(),
            'scheduled_publish_at' => $this->scheduled_publish_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
