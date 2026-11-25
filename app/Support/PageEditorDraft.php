<?php

namespace App\Support;

use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Models\Page;
use App\Domain\Staff\Models\Staff;
use Illuminate\Support\Facades\Cache;

/**
 * The visual editor's working copy of a page's blocks, per staff member.
 * Kept in the cache while editing (so the preview shows unsaved changes);
 * "Guardar borrador" stores it in pages.draft_blocks, "Publicar" in pages.blocks.
 */
final class PageEditorDraft
{
    /** @return list<array{type: string, data: array<string, mixed>}> */
    public static function get(Page $page, Staff $staff): array
    {
        return Cache::get(self::key($page, $staff)) ?? self::saved($page);
    }

    /** @param  list<array{type: string, data: array<string, mixed>}>  $blocks */
    public static function put(Page $page, Staff $staff, array $blocks): void
    {
        Cache::put(self::key($page, $staff), array_values($blocks), now()->addHours(12));
    }

    public static function forget(Page $page, Staff $staff): void
    {
        Cache::forget(self::key($page, $staff));
    }

    /** Last saved state: the draft if there is one, otherwise what is published (or the Stitch defaults for the home page). */
    public static function saved(Page $page): array
    {
        return array_values($page->draft_blocks ?? $page->blocks ?? ($page->isHome() ? DefaultContent::home() : []));
    }

    private static function key(Page $page, Staff $staff): string
    {
        return "page-editor:{$page->id}:{$staff->id}";
    }
}
