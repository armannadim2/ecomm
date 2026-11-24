<?php

namespace App\Http\Controllers\Store;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Content\Blocks\BlockRenderer;
use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug, StoreSettings $settings, BlockRenderer $renderer): View|RedirectResponse
    {
        if ($slug === Page::HOME_SLUG) {
            return redirect()->route('store.home', status: 301);
        }

        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('store.page', [
            'page' => $page,
            'body' => $page->renderedBody($settings),
            'blocks' => $renderer->prepare($page->blocks),
        ]);
    }

    /** Only production is indexed; staging and local tell crawlers to stay out. */
    public function robots(): Response
    {
        $body = app()->isProduction()
            ? "User-agent: *\nDisallow: /admin\nDisallow: /cuenta\nDisallow: /buscar\n\nSitemap: ".route('store.sitemap')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** XML sitemap for search engines, cached for an hour. */
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => view('store.sitemap', [
            'products' => StorefrontProducts::query()->without(['media', 'variants', 'brand'])
                ->select(['id', 'slug', 'updated_at'])->get(),
            'categories' => Category::query()->where('is_visible', true)->get(['id', 'slug', 'updated_at']),
            'pages' => Page::query()->published()->where('slug', '!=', Page::HOME_SLUG)->get(['id', 'slug', 'updated_at']),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
