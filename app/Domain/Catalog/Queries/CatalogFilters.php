<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filters of the storefront listings (category and search pages, Stitch "Cerca i Catàleg").
 *
 * Query string: ?stock=1 · ?marca[]=slug · ?min=10&max=50 (euros) · ?categoria=slug (search page only).
 * Each facet is counted with every filter applied except its own (see ProductListing).
 */
final readonly class CatalogFilters
{
    /** @param  list<string>  $brands  brand slugs */
    public function __construct(
        public bool $inStock = false,
        public array $brands = [],
        public ?int $minCents = null,
        public ?int $maxCents = null,
        public ?string $category = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $euros = function (string $key) use ($request): ?int {
            $value = $request->query($key);

            return is_numeric($value) && $value >= 0 && $value < 1_000_000 ? (int) round((float) $value * 100) : null;
        };

        $brands = collect((array) $request->query('marca', []))
            ->filter(fn ($slug) => is_string($slug) && preg_match('/^[a-z0-9-]{1,80}$/', $slug))
            ->unique()->take(20)->values()->all();

        $category = $request->query('categoria');

        return new self(
            inStock: $request->boolean('stock'),
            brands: $brands,
            minCents: $euros('min'),
            maxCents: $euros('max'),
            category: is_string($category) && preg_match('/^[a-z0-9-]{1,80}$/', $category) ? $category : null,
        );
    }

    public function isEmpty(): bool
    {
        return ! $this->inStock && $this->brands === [] && $this->minCents === null && $this->maxCents === null && $this->category === null;
    }

    /**
     * @param  Builder<Product>  $query
     * @param  list<'stock'|'brand'|'price'|'category'>  $except  filters left out (to count a facet)
     */
    public function apply(Builder $query, array $except = []): Builder
    {
        if ($this->inStock && ! in_array('stock', $except, true)) {
            $query->whereHas('variants', fn (Builder $v) => $v->where(fn (Builder $w) => $w->where('track_stock', false)->orWhere('stock', '>', 0)));
        }

        if ($this->brands !== [] && ! in_array('brand', $except, true)) {
            $query->whereHas('brand', fn (Builder $b) => $b->whereIn('slug', $this->brands));
        }

        if (($this->minCents !== null || $this->maxCents !== null) && ! in_array('price', $except, true)) {
            $query->whereHas('variants', function (Builder $v) {
                if ($this->minCents !== null) {
                    $v->where('price_cents', '>=', $this->minCents);
                }
                if ($this->maxCents !== null) {
                    $v->where('price_cents', '<=', $this->maxCents);
                }
            });
        }

        if ($this->category !== null && ! in_array('category', $except, true)) {
            $query->whereHas('categories', fn (Builder $c) => $c->where('is_visible', true)
                ->where(fn (Builder $w) => $w->where('slug', $this->category)
                    ->orWhereHas('parent', fn (Builder $p) => $p->where('slug', $this->category))));
        }

        return $query;
    }
}
