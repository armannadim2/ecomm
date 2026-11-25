<?php

namespace App\Filament\Resources\Products\Tables;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Filament\Resources\Products\ProductActions;
use App\Support\Money;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['brand', 'categories', 'variants', 'media', 'createdBy']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->label('')
                    ->collection(Product::IMAGES)
                    ->conversion('thumb')
                    ->limit(1)
                    ->square()
                    ->imageSize(48),
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record) => $record->brand?->name),
                TextColumn::make('categories.name')
                    ->label('Categorías')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('Precio')
                    ->state(function (Product $record): string {
                        $prices = $record->variants->pluck('price_cents');

                        if ($prices->isEmpty()) {
                            return '—';
                        }

                        return $prices->min() === $prices->max()
                            ? Money::format($prices->min())
                            : 'desde '.Money::format($prices->min());
                    }),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->state(fn (Product $record) => $record->variants->sum('stock'))
                    ->badge()
                    ->color(fn (Product $record) => $record->variants->contains(fn ($v) => $v->isLowStock()) ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->description(fn (Product $record) => $record->scheduled_publish_at && ! $record->isPublished()
                        ? 'Se publica el '.$record->scheduled_publish_at->timezone('Europe/Madrid')->format('d/m H:i')
                        : null)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(ProductStatus::class),
                SelectFilter::make('categories')->label('Categoría')->relationship('categories', 'name')->multiple()->preload(),
                SelectFilter::make('brand')->label('Marca')->relationship('brand', 'name')->preload(),
                Filter::make('low_stock')
                    ->label('Con poco stock')
                    ->query(fn (Builder $query) => $query->whereHas('variants', fn (Builder $q) => $q
                        ->where('track_stock', true)
                        ->whereColumn('stock', '<=', 'low_stock_threshold'))),
                TrashedFilter::make()->label('Papelera'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ProductActions::publish(),
                    ProductActions::reject(),
                    ProductActions::submitForReview(),
                    ProductActions::unpublish(),
                    ProductActions::schedule(),
                    ProductActions::cancelSchedule(),
                    ProductActions::adjustStock(),
                    ProductActions::duplicate(),
                    DeleteAction::make()->label('Enviar a la papelera'),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Enviar a la papelera'),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Todavía no hay productos')
            ->emptyStateDescription('Crea tu primer producto con el botón «Nuevo producto».');
    }
}
