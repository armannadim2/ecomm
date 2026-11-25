<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Blocks\BlockRenderer;
use App\Domain\Content\Blocks\PageEditing;
use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;
use App\Http\Controllers\Controller;
use App\Support\PageEditorDraft;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Live preview inside the visual editor (iframe). Renders the real storefront page from the
 * staff member's unsaved draft, in the language chosen in the editor, with editing markers.
 * Staff only; never indexed.
 */
class PageEditorPreviewController extends Controller
{
    public function __invoke(Request $request, Page $page, BlockRenderer $renderer, StoreSettings $settings): Response
    {
        abort_unless($request->user()?->can('update', $page), 403);

        $locale = (string) $request->query('locale', 'es');
        app()->setLocale(array_key_exists($locale, config('app.available_locales')) ? $locale : 'es');

        PageEditing::start();

        try {
            $blocks = $renderer->prepare(PageEditorDraft::get($page, $request->user()), includeHidden: true);

            $html = $page->isHome()
                ? view('store.home', ['page' => $page, 'blocks' => $blocks])->render()
                : view('store.page', ['page' => $page, 'body' => $page->renderedBody($settings), 'blocks' => $blocks])->render();
        } finally {
            PageEditing::stop();
        }

        return response($html)->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }
}
