<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/** Shared helpers for the REST API v1 (docs: docs/API.md). */
abstract class ApiController extends Controller
{
    /** ?per_page=1..100 (default 25). */
    protected function perPage(Request $request): int
    {
        return max(1, min(100, (int) $request->query('per_page', 25)));
    }

    /** ?updated_since=2026-11-25T10:00:00Z — only records changed after that moment (for syncing). */
    protected function filterUpdatedSince(Builder $query, Request $request, string $column = 'updated_at'): void
    {
        if (! $request->filled('updated_since')) {
            return;
        }

        try {
            $since = Carbon::parse((string) $request->query('updated_since'));
        } catch (\Throwable) {
            throw ValidationException::withMessages(['updated_since' => 'Usa una fecha ISO 8601, por ejemplo 2026-11-25T10:00:00Z.']);
        }

        $query->where($query->getModel()->qualifyColumn($column), '>', $since);
    }
}
