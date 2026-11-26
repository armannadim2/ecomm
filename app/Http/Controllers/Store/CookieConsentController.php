<?php

namespace App\Http\Controllers\Store;

use App\Domain\Marketing\Settings\AnalyticsSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stores the visitor's cookie choice: a readable cookie (so cached pages can decide in the browser
 * which tags to load) and a log row as proof. "Reject" is a single click, like "Accept".
 */
class CookieConsentController extends Controller
{
    public const COOKIE = 'tm_consent';

    /** AEPD: ask again after at most 24 months; we use 12. */
    private const DAYS = 365;

    public function __invoke(Request $request, AnalyticsSettings $settings): JsonResponse
    {
        $data = $request->validate([
            'analytics' => ['required', 'boolean'],
            'marketing' => ['required', 'boolean'],
        ]);

        $previous = json_decode((string) $request->cookie(self::COOKIE), true);
        $id = is_array($previous) && Str::isUuid($previous['id'] ?? null) ? $previous['id'] : (string) Str::uuid();

        $choice = [
            'id' => $id,
            'v' => $settings->consent_version,
            'analytics' => (bool) $data['analytics'],
            'marketing' => (bool) $data['marketing'],
        ];

        DB::table('cookie_consents')->insert([
            'consent_id' => $id,
            'version' => $settings->consent_version,
            'analytics' => $choice['analytics'],
            'marketing' => $choice['marketing'],
            // Salted hash: proves repeat choices from the same connection without keeping the IP.
            'ip_hash' => $request->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true])->withCookie(Cookie::make(
            self::COOKIE, (string) json_encode($choice), self::DAYS * 24 * 60,
            secure: $request->isSecure(), httpOnly: false, sameSite: 'lax',
        ));
    }
}
