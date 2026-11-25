<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * A filtered, sorted product listing with its facet counts (category and search pages).
 *
 * $scope limits the products (a category and its children, or the ids found by the search);
 * CatalogFilters narrows them further.
 */
final class ProductListing
{
    /** @param  Closure(Builder<Product>): void  $scope */
    public function __construct(
        private readonly Closure $scope,
        public readonly CatalogFilters $filters,
    ) {}

    /** @return Builder<Product> published products in scope, filters applied except $except */
    private function base(array $except = []): Builder
    {
        $query = Product::query()->published();
        ($this->scope)($query);

        return $this->filters->apply($query, $except);
    }

    /**
     * @param  (Closure(Builder<Product>): void)|null  $order  custom order (search relevance)
     */
    public function paginate(string $sort, int $perPage = 24, ?Closure $order = null): LengthAwarePaginator
    {
        $query = $this->filters->apply(tap(StorefrontProducts::query(), $this->scope));

        $order && ! array_key_exists($sort, StorefrontProducts::SORTS)
            ? $order($query)
            : StorefrontProducts::sort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return Collection<int, array{slug: string, name: string, count: int}> brands with products, most first */
    public function brandFacets(): Collection
    {
        $ids = $this->base(['brand'])->select('products.id');

        return Brand::query()
            ->where('is_visible', true)
            ->whereHas('products', fn (Builder $q) => $q->whereIn('products.id', $ids))
            ->withCount(['products' => fn (Builder $q) => $q->whereIn('products.id', $ids)])
            ->get()
            ->map(fn (Brand $b) => ['slug' => $b->slug, 'name' => $b->name, 'count' => (int) $b->products_count])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * @param  Collection<int, Category>  $categories  categories to count (subcategories, or all top-level ones)
     * @return Collection<int, array{slug: string, name: string, count: int}> only those with products
     */
    public function categoryFacets(Collection $categories): Collection
    {
        $ids = $this->base(['category'])->select('products.id');

        return $categories
            ->map(function (Category $category) use ($ids) {
                $categoryIds = $category->relationLoaded('children') ? $category->children->pluck('id')->push($category->id) : collect([$category->id]);

                return [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'count' => Product::query()->whereIn('id', $ids)
                        ->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $categoryIds))
                        ->count(),
                ];
            })
            ->filter(fn (array $facet) => $facet['count'] > 0)
            ->values();
    }

    /** Products in stock with every other filter applied (count shown next to the toggle). */
    public function inStockCount(): int
    {
        return (new CatalogFilters(true, $this->filters->brands, $this->filters->minCents, $this->filters->maxCents, $this->filters->category))
            ->apply(tap(Product::query()->published(), $this->scope))
            ->count();
    }

    /** @return array{0: int, 1: int}|null cheapest and dearest variant price (cents) in scope, for the price inputs */
    public function priceBounds(): ?array
    {
        $prices = ProductVariant::query()
            ->whereIn('product_id', $this->base(['price'])->select('products.id'))
            ->selectRaw('MIN(price_cents) as lo, MAX(price_cents) as hi')
            ->first();

        return $prices && $prices->hi !== null ? [(int) $prices->lo, (int) $prices->hi] : null;
    }
}
