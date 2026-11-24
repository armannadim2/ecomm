<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates a unique slug from the `name` attribute when the slug is empty.
 *
 * @mixin Model
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            $source = blank($model->slug) ? $model->name : $model->slug;
            $model->slug = static::uniqueSlug($source, $model->getKey());
        });
    }

    public static function uniqueSlug(string $source, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->withoutGlobalScopes()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
