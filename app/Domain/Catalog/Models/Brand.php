<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Catalog\Policies\BrandPolicy;
use App\Support\HasUniqueSlug;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['name', 'slug', 'is_visible'])]
#[UseFactory(BrandFactory::class)]
#[UsePolicy(BrandPolicy::class)]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('catalog')
            ->logOnly(['name', 'slug', 'is_visible'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
