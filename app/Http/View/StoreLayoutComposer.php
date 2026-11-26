<?php

namespace App\Http\View;

use App\Domain\Catalog\Models\Category;
use App\Domain\Content\Blocks\Block;
use App\Domain\Content\Models\Page;
use App\Domain\Content\Settings\StorefrontContentSettings;
use App\Domain\Marketing\Models\Campaign;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Data every storefront page needs (header menu, footer, store identity).
 *
 * Variables:
 *   $store           StoreSettings
 *   $logoUrl         ?string
 *   $menuCategories  list<array{name: string, slug: string, children: list<array{name: string, slug: string}>}>
 *   $footerPages     list<array{title: string, slug: string}>
 *   $campaign        ?Campaign live right now
 *   $pageSlugs       list<string> slugs of published pages
 *   $layout          array{announcement: Block, header: Block, valueProps: list<Block>, footer: Block}
 *                    editable header/footer texts (admin: "Cabecera y pie")
 *
 * Only plain arrays and ids are cached: Laravel refuses to unserialize objects
 * from the cache (config cache.serializable_classes = false) as a security measure.
 */
class StoreLayoutComposer
{
    public function __construct(
        private readonly StoreSettings $settings,
        private readonly StorefrontContentSettings $content,
    ) {}

    /** @var array<string, mixed>|null Built once per request (the composer is a scoped singleton). */
    private ?array $data = null;

    /**
     * Runs for every `store.*` view, including each product card and page section, so the
     * data is built once and reused: before this, the home page made ~300 database queries.
     */
    public function compose(View $view): void
    {
        $view->with($this->data ??= $this->build());
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        return [
            'store' => $this->settings,
            'logoUrl' => $this->settings->logo_path ? Storage::disk('public')->url($this->settings->logo_path) : null,
            'menuCategories' => Cache::remember('store.menu', now()->addMinutes(10), fn () => Category::query()
                ->where('is_visible', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_visible', true)])
                ->orderBy('position')
                ->get()
                ->map(fn (Category $c) => [
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'children' => $c->children->map(fn (Category $child) => ['name' => $child->name, 'slug' => $child->slug])->all(),
                ])
                ->all()),
            'campaign' => $this->campaign(),
            'layout' => [
                'announcement' => new Block('announcement', $this->content->announcement),
                'header' => new Block('header', $this->content->header),
                'valueProps' => (new Block('layout', ['items' => $this->content->value_props]))->items('items'),
                'footer' => new Block('footer', $this->content->footer),
            ],
            'footerPages' => Cache::remember('store.footer.'.app()->getLocale(), now()->addMinutes(10), fn () => Page::query()
                ->published()
                ->where('show_in_footer', true)
                ->orderBy('position')
                ->get(['title', 'slug', 'translations'])
                ->map(fn (Page $p) => ['title' => $p->localizedTitle(), 'slug' => $p->slug])
                ->all()),
            // Slugs of all published pages, so links like "Envíos y devoluciones" only appear when the page exists.
            'pageSlugs' => Cache::remember('store.page-slugs', now()->addMinutes(10), fn () => Page::query()
                ->published()
                ->pluck('slug')
                ->all()),
        ];
    }

    /** The live campaign id is re-checked every minute so campaigns start and end on time. */
    private function campaign(): ?Campaign
    {
        $id = Cache::remember('store.campaign', now()->addMinute(), fn () => Campaign::current()?->id ?? 0);

        return $id ? Campaign::query()->with('media')->find($id) : null;
    }
}
