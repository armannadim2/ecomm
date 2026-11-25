<?php

namespace App\Http\Middleware;

use App\Domain\Integrations\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The REST API is only for API clients created in "Integraciones → Claves API".
 * Customer or staff tokens/sessions are refused, and so are revoked clients.
 */
class EnsureApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $client = $request->user();

        if (! $client instanceof ApiClient || ! $client->isActive()) {
            return response()->json(['message' => 'Clave API no válida o revocada.'], 401);
        }

        // Shown to the owner ("last used"); written at most once a minute.
        if ($client->last_used_at === null || $client->last_used_at->lt(now()->subMinute())) {
            $client->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        return $next($request);
    }
}
