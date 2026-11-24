<?php

namespace App\Filament\Widgets;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Filament\Resources\Products\ProductResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    protected function getStats(): array
    {
        $lowStock = ProductVariant::query()
            ->where('track_stock', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->whereHas('product')
            ->count();

        return [
            Stat::make('Productos publicados', Product::query()->where('status', ProductStatus::Published)->count())
                ->description('Visibles en la tienda')
                ->color('success')
                ->url(ProductResource::getUrl('index', ['filters' => ['status' => ['value' => 'published']]])),
            Stat::make('Borradores', Product::query()->where('status', ProductStatus::Draft)->count())
                ->description('Pendientes de publicar')
                ->color('gray')
                ->url(ProductResource::getUrl('index', ['filters' => ['status' => ['value' => 'draft']]])),
            Stat::make('Variantes con poco stock', $lowStock)
                ->description($lowStock > 0 ? 'Revisa y repón stock' : 'Todo en orden')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->url(ProductResource::getUrl('index', ['filters' => ['low_stock' => ['isActive' => true]]])),
        ];
    }
}
