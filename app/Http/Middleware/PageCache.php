<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Keys and invalidation for CachePublicPage. Only strings and arrays are cached (cache.serializable_classes = false). */
final class PageCache
{
    private const VERSION_KEY = 'page-cache.version';

    public static function key(Request $request): string
    {
        $version = (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
        $query = $request->query();
        ksort($query);

        return 'page:'.$version.':'.app()->getLocale().':'.sha1($request->getPathInfo().'?'.http_build_query($query));
    }

    /** Drops every cached page at once; old entries expire on their own. */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }
}
