<?php

namespace App\Filament\Resources\Categories;

use App\Domain\Catalog\Models\Category;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Select::make('parent_id')
                    ->label('Dentro de la categoría')
                    ->helperText('Opcional. Por ejemplo, «Camisetas» dentro de «Ropa».')
                    ->relationship(
                        'parent',
                        'name',
                        fn (Builder $query, ?Category $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->getKey())),
                    )
                    ->searchable()
                    ->preload(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3),
                Toggle::make('is_visible')
                    ->label('Visible en la tienda')
                    ->default(true),
                Section::make('Buscadores (SEO)')
                    ->collapsed()
                    ->schema([
                        TextInput::make('slug')->label('Dirección web')->prefix('/categoria/')
                            ->helperText('Se genera automáticamente si lo dejas vacío.')->maxLength(255),
                        TextInput::make('seo_title')->label('Título para Google')->maxLength(70),
                        Textarea::make('seo_description')->label('Descripción para Google')->rows(2)->maxLength(160),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()
                    ->description(fn (Category $record) => $record->parent ? 'En: '.$record->parent->name : null),
                TextColumn::make('products_count')->label('Productos')->counts('products'),
                IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->filters([
                TrashedFilter::make()->label('Papelera'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Enviar a la papelera'),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Enviar a la papelera'),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
