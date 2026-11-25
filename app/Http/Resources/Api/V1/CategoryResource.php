<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Catalog\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ulid,
            'parent_id' => $this->whenLoaded('parent', fn () => $this->parent?->ulid),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'position' => $this->position,
            'is_visible' => (bool) $this->is_visible,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'url' => route('store.category', $this->slug),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
