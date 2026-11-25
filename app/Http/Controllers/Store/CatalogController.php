<?php

namespace App\Http\Controllers\Store;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Queries\CatalogFilters;
use App\Domain\Catalog\Queries\ProductListing;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Content\Blocks\BlockRenderer;
use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Models\Page;
use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /** Home page: the blocks of the page "inicio" (page builder), or the default Stitch content. */
    public function home(BlockRenderer $renderer): View
    {
        $page = Page::query()->where('slug', Page::HOME_SLUG)->first();
        $raw = $page && $page->is_published && $page->blocks ? $page->blocks : DefaultContent::home();

        return view('store.home', [
            'page' => $page,
            'blocks' => $renderer->prepare($raw),
        ]);
    }

    /** All departments (Stitch "Categories"): cover, product count, newest product, subcategories. */
    public function categories(): View
    {
        $departments = Category::query()
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_visible', true)->orderBy('position')])
            ->orderBy('position')
            ->get()
            ->map(function (Category $category) {
                $featured = StorefrontProducts::sort(StorefrontProducts::inCategory($category), 'novedades')->first();

                return [
                    'category' => $category,
                    'count' => StorefrontProducts::inCategory($category)->count(),
                    'featured' => $featured,
                    'cover' => StorefrontProducts::sort(StorefrontProducts::inCategory($category), 'novedades')
                        ->whereHas('media')->first()?->imageUrl('medium'),
                ];
            });

        return view('store.categories', ['departments' => $departments]);
    }

    public function category(Request $request, string $slug): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->with(['parent', 'children' => fn ($q) => $q->where('is_visible', true)->orderBy('position')])
            ->firstOrFail();

        $ids = $category->children->pluck('id')->push($category->id);
        $listing = new ProductListing(
            fn (Builder $q) => $q->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $ids)),
            CatalogFilters::fromRequest($request),
        );
        $sort = $request->string('orden')->toString();

        return view('store.category', [
            'category' => $category,
            ...$this->listingData($listing, $sort, $category->children),
        ]);
    }

    public function product(string $slug): View
    {
        $product = StorefrontProducts::query()
            ->where('slug', $slug)
            ->with(['categories' => fn ($q) => $q->where('is_visible', true)])
            ->firstOrFail();

        return view('store.product', [
            'product' => $product,
            'variants' => $product->variants,
            'images' => $product->getMedia(Product::IMAGES),
            'related' => StorefrontProducts::related($product)->get(),
        ]);
    }

    public function search(Request $request): View
    {
        $term = trim($request->string('q')->limit(100, '')->toString());

        if (mb_strlen($term) < 2) {
            return view('store.search', ['term' => $term, 'products' => null]);
        }

        // Matching ids by relevance (Scout), then the same filtered listing as a category.
        $ids = Product::search($term)->take(500)->keys()->all();
        $listing = new ProductListing(fn (Builder $q) => $q->whereIn('products.id', $ids), CatalogFilters::fromRequest($request));
        $sort = $request->string('orden')->toString() ?: 'relevancia';

        $relevance = function (Builder $q) use ($ids) {
            if ($ids === []) {
                return;
            }
            $cases = collect($ids)->values()->map(fn ($id, $i) => 'WHEN '.(int) $id.' THEN '.$i)->implode(' ');
            $q->orderByRaw("CASE products.id {$cases} END");
        };

        $topCategories = Category::query()->where('is_visible', true)->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_visible', true)])->orderBy('position')->get();

        return view('store.search', [
            'term' => $term,
            ...$this->listingData($listing, $sort, $topCategories, $relevance, ['relevancia' => 'Más relevantes']),
        ]);
    }

    /**
     * View data shared by the category and search listings.
     *
     * @param  Collection<int, Category>  $sections
     * @param  array<string, string>  $extraSorts
     * @return array<string, mixed>
     */
    private function listingData(ProductListing $listing, string $sort, Collection $sections, ?Closure $order = null, array $extraSorts = []): array
    {
        $sorts = $extraSorts + StorefrontProducts::SORTS;
        $currentSort = array_key_exists($sort, $sorts) ? $sort : array_key_first($sorts);

        return [
            'products' => $listing->paginate($currentSort, 24, $order),
            'filters' => $listing->filters,
            'brands' => $listing->brandFacets(),
            'sections' => $listing->categoryFacets($sections),
            'inStockCount' => $listing->inStockCount(),
            'priceBounds' => $listing->priceBounds(),
            'sorts' => $sorts,
            'currentSort' => $currentSort,
        ];
    }
}
