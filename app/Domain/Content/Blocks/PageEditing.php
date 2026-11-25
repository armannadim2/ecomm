<?php

namespace App\Domain\Content\Blocks;

/**
 * Whether the current request renders the visual editor preview (staff only).
 * Views then add the markers the editor script needs (block outlines, click-to-edit texts).
 */
final class PageEditing
{
    private static bool $active = false;

    public static function start(): void
    {
        self::$active = true;
    }

    public static function stop(): void
    {
        self::$active = false;
    }

    public static function active(): bool
    {
        return self::$active;
    }
}
