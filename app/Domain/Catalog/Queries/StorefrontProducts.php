<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Product queries for the storefront and the mobile API: only what
 * customers may see, with everything a product card needs loaded.
 */
final class StorefrontProducts
{
    public const SORTS = [
        'novedades' => 'Novedades',
        'precio-asc' => 'Precio: de menor a mayor',
        'precio-desc' => 'Precio: de mayor a menor',
        'nombre' => 'Nombre (A–Z)',
    ];

    /** @return Builder<Product> */
    public static function query(): Builder
    {
        return Product::query()
            ->published()
            ->with(['media', 'variants', 'brand', 'categories' => fn ($q) => $q->where('is_visible', true)])
            ->withMin('variants as min_price_cents', 'price_cents');
    }

    /** @return Builder<Product> */
    public static function inCategory(Category $category): Builder
    {
        $ids = $category->children()->where('is_visible', true)->pluck('id')->push($category->id);

        return self::query()->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $ids));
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public static function sort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'precio-asc' => $query->orderBy('min_price_cents'),
            'precio-desc' => $query->orderByDesc('min_price_cents'),
            'nombre' => $query->orderBy('name'),
            default => $query->latest('published_at')->latest('id'),
        };
    }

    /** @return Builder<Product> */
    public static function related(Product $product, int $limit = 4): Builder
    {
        $categoryIds = $product->categories->pluck('id');

        return self::query()
            ->whereKeyNot($product->getKey())
            ->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds))
            ->inRandomOrder()
            ->limit($limit);
    }
}
