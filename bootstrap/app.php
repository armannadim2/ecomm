<?php

use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Http\Middleware\CachePublicPage;
use App\Http\Middleware\EnsureApiClient;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetStorefrontLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [SetStorefrontLocale::class]);

        // Redsys may send the customer back with a cross-site POST (no CSRF token); that page only redirects.
        $middleware->validateCsrfTokens(except: ['pago/redsys/*']);

        // The cookie choice is read by the browser (cached pages), so it is not encrypted. It holds no personal data.
        $middleware->encryptCookies(except: ['tm_consent']);

        // REST API for outside systems (routes/api.php): key scopes and "is this an API client?".
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'api.client' => EnsureApiClient::class,
            'page.cache' => CachePublicPage::class,
        ]);

        // Hostinger and Cloudflare sit in front of the app; trust their forwarded headers.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Business rules broken through the API ("not enough stock"…) are the caller's mistake: 422.
        $exceptions->render(fn (DomainRuleException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e->getMessage()], 422)
            : null);
    })->create();
