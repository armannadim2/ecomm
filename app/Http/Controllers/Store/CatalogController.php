<?php

namespace App\Http\Controllers\Store;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Content\Blocks\BlockRenderer;
use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Models\Page;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function category(Request $request, string $slug): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->with(['parent', 'children' => fn ($q) => $q->where('is_visible', true)])
            ->firstOrFail();

        $sort = $request->string('orden')->toString();

        return view('store.category', [
            'category' => $category,
            'products' => StorefrontProducts::sort(StorefrontProducts::inCategory($category), $sort)
                ->paginate(24)
                ->withQueryString(),
            'sorts' => StorefrontProducts::SORTS,
            'currentSort' => array_key_exists($sort, StorefrontProducts::SORTS) ? $sort : 'novedades',
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

        $products = mb_strlen($term) >= 2
            ? Product::search($term)
                ->query(fn ($q) => $q->published()->with(['media', 'variants', 'brand'])
                    ->withMin('variants as min_price_cents', 'price_cents'))
                ->paginate(24)
                ->withQueryString()
            : null;

        return view('store.search', ['term' => $term, 'products' => $products]);
    }
}
