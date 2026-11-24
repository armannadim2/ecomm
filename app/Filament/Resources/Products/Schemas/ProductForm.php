<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Filament\Support\MoneyInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        Group::make([
                            self::basics(),
                            self::photos(),
                            self::variants(),
                        ])->columnSpan(['lg' => 2]),

                        Group::make([
                            self::status(),
                            self::organisation(),
                            self::seo(),
                        ])->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    private static function basics(): Section
    {
        return Section::make('Información básica')
            ->schema([
                TextInput::make('name')
                    ->label('Nombre del producto')
                    ->placeholder('Ej.: Camiseta de algodón orgánico')
                    ->required()
                    ->maxLength(255),
                Textarea::make('short_description')
                    ->label('Resumen')
                    ->helperText('Una o dos frases. Se muestra en el listado de la tienda.')
                    ->rows(2)
                    ->maxLength(500),
                RichEditor::make('description')
                    ->label('Descripción completa')
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
            ]);
    }

    private static function photos(): Section
    {
        return Section::make('Fotos')
            ->description('Arrastra las fotos aquí. La primera será la principal. Puedes reordenarlas.')
            ->schema([
                SpatieMediaLibraryFileUpload::make('images')
                    ->hiddenLabel()
                    ->collection(Product::IMAGES)
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->maxFiles(10)
                    ->panelLayout('grid')
                    ->helperText('JPG, PNG o WebP. Máximo 5 MB por foto, hasta 10 fotos.'),
            ]);
    }

    private static function variants(): Section
    {
        return Section::make('Precio y stock')
            ->description('Si el producto tiene tallas o colores, añade una variante por cada combinación.')
            ->schema([
                Repeater::make('variants')
                    ->hiddenLabel()
                    ->relationship()
                    ->orderColumn('position')
                    ->minItems(1)
                    ->defaultItems(1)
                    ->collapsible()
                    ->addActionLabel('Añadir variante (talla, color…)')
                    ->itemLabel(fn (array $state): string => filled($state['name'] ?? null) ? $state['name'] : 'Variante')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('name')
                                ->label('Nombre de la variante')
                                ->placeholder('Ej.: Talla M / Azul')
                                ->helperText('Déjalo vacío si el producto no tiene variantes.')
                                ->maxLength(255),
                            TextInput::make('sku')
                                ->label('Referencia (SKU)')
                                ->helperText('Código único para identificar el artículo en el almacén.')
                                ->required()
                                ->maxLength(64)
                                ->unique(ProductVariant::class, 'sku', ignoreRecord: true),
                            MoneyInput::make('price_cents')
                                ->label('Precio de venta (IVA incluido)')
                                ->required(),
                            MoneyInput::make('compare_at_price_cents')
                                ->label('Precio anterior (opcional)')
                                ->helperText('Se muestra tachado para indicar una oferta.'),
                            TextInput::make('stock')
                                ->label('Stock')
                                ->integer()
                                ->minValue(0)
                                ->default(0)
                                ->disabled(fn (?ProductVariant $record) => $record?->exists === true)
                                ->dehydrated(fn (?ProductVariant $record) => $record?->exists !== true)
                                ->helperText(fn (?ProductVariant $record) => $record?->exists
                                    ? 'Para cambiarlo usa el botón «Ajustar stock» (queda registrado).'
                                    : 'Unidades disponibles al crear la variante.'),
                        ]),
                        Fieldset::make('Más opciones')
                            ->columns(['default' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('barcode')->label('Código de barras (EAN)')->maxLength(64),
                                TextInput::make('weight_grams')->label('Peso (gramos)')->integer()->minValue(0)
                                    ->helperText('Sirve para calcular el envío.'),
                                TextInput::make('low_stock_threshold')->label('Aviso de poco stock')->integer()
                                    ->minValue(0)->default(5)
                                    ->helperText('Te avisamos cuando queden estas unidades o menos.'),
                                Toggle::make('track_stock')->label('Controlar stock')->default(true)
                                    ->helperText('Desactívalo para productos bajo pedido.'),
                            ]),
                    ]),
            ]);
    }

    private static function status(): Section
    {
        return Section::make('Estado')
            ->schema([
                TextEntry::make('status')
                    ->hiddenLabel()
                    ->badge()
                    ->visibleOn('edit'),
                TextEntry::make('status_help')
                    ->hiddenLabel()
                    ->state('Usa los botones «Publicar» u «Ocultar» de arriba para cambiar el estado.')
                    ->color('gray')
                    ->visibleOn('edit'),
                TextEntry::make('status_help_create')
                    ->hiddenLabel()
                    ->state('Los productos nuevos se guardan como borrador. Podrás publicarlo después de guardarlo.')
                    ->color('gray')
                    ->visibleOn('create'),
            ]);
    }

    private static function organisation(): Section
    {
        return Section::make('Organización')
            ->schema([
                Select::make('categories')
                    ->label('Categorías')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')->label('Nombre de la marca')->required()->maxLength(255),
                    ]),
                Select::make('tax_class')
                    ->label('IVA')
                    ->options(TaxClass::class)
                    ->default(TaxClass::General)
                    ->required()
                    ->helperText('La mayoría de productos llevan el tipo general (21 %). Consulta con tu gestor si tienes dudas.'),
            ]);
    }

    private static function seo(): Section
    {
        return Section::make('Buscadores (SEO)')
            ->description('Opcional. Si lo dejas vacío usamos el nombre y el resumen.')
            ->collapsed()
            ->schema([
                TextInput::make('slug')
                    ->label('Dirección web')
                    ->prefix('/producto/')
                    ->helperText('Se genera automáticamente a partir del nombre.')
                    ->maxLength(255),
                TextInput::make('seo_title')->label('Título para Google')->maxLength(70),
                Textarea::make('seo_description')->label('Descripción para Google')->rows(3)->maxLength(160),
            ]);
    }
}
