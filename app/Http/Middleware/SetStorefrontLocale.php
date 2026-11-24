<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront language (es / en / ca).
 *
 * The language the visitor picked with the switcher (session), otherwise Spanish.
 * The admin panel (and its Livewire requests) always stays in Spanish.
 */
class SetStorefrontLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*') || $request->routeIs('filament.*') || $this->isAdminLivewire($request)) {
            return $next($request);
        }

        $available = array_keys(config('app.available_locales'));
        $chosen = $request->hasSession() ? $request->session()->get('locale') : null;

        // No automatic Accept-Language detection: URLs carry no language, so crawlers (which send
        // "en") would otherwise index the shop in English. Spanish unless the visitor picks another.
        $locale = in_array($chosen, $available, true) ? $chosen : config('app.locale');

        app()->setLocale($locale);

        return $next($request);
    }

    private function isAdminLivewire(Request $request): bool
    {
        return $request->is('livewire*') && str_contains((string) $request->headers->get('referer'), '/admin');
    }
}
