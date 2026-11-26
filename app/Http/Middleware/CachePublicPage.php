<?php

namespace App\Http\Middleware;

use App\Domain\Content\Blocks\PageEditing;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for catalogue pages (STORE-07), built for shared hosting (database cache, no Varnish).
 * Only visitors who see the "public" version are served from cache: not logged in, empty cart,
 * no flash messages. The CSRF token is swapped in on every hit, so forms keep working.
 * Everything is dropped at once (version bump) when staff change products, categories, pages,
 * campaigns or settings — see PageCache::flush().
 */
class CachePublicPage
{
    private const TOKEN_PLACEHOLDER = '__CSRF_TOKEN_PLACEHOLDER__';

    public const TTL_MINUTES = 10;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $key = PageCache::key($request);
        $hit = Cache::get($key);

        if (is_array($hit) && isset($hit['content'], $hit['type'])) {
            return response(str_replace(self::TOKEN_PLACEHOLDER, csrf_token(), $hit['content']), 200, [
                'Content-Type' => $hit['type'],
                'X-Page-Cache' => 'hit',
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && $this->stillPublic($request)) {
            $content = (string) $response->getContent();
            $token = csrf_token();

            Cache::put($key, [
                'content' => $token ? str_replace($token, self::TOKEN_PLACEHOLDER, $content) : $content,
                'type' => (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
            ], now()->addMinutes(self::TTL_MINUTES));

            $response->headers->set('X-Page-Cache', 'miss');
        }

        return $response;
    }

    private function cacheable(Request $request): bool
    {
        return config('app.page_cache', true)
            && $request->isMethod('GET')
            && $this->stillPublic($request);
    }

    /** Checked before and after rendering: a page that put something in the session is not shared. */
    private function stillPublic(Request $request): bool
    {
        if (auth('web')->check() || PageEditing::active()) {
            return false;
        }

        if (! $request->hasSession()) {
            return true;
        }

        $session = $request->session();

        return empty($session->get('cart'))
            && empty($session->get('_flash.new'))
            && empty($session->get('_flash.old'))
            && ! $session->has('errors');
    }
}
