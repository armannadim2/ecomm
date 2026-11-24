<?php

namespace App\Filament\Resources\Pages;

use App\Domain\Content\Models\Page;
use App\Filament\Blocks\PageBlocks;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = 'página';

    protected static ?string $pluralModelLabel = 'páginas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Group::make([
                        Section::make()->schema([
                            TextInput::make('title')->label('Título')->required()->maxLength(150)
                                ->disabled(fn (?Page $record) => $record?->isHome())
                                ->dehydrated(fn (?Page $record) => ! $record?->isHome()),
                            RichEditor::make('body')->label('Contenido')
                                ->helperText('Texto de la página (en español). Debajo puedes añadir bloques de diseño.')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'link'],
                                    ['h2', 'h3', 'bulletList', 'orderedList'],
                                    ['undo', 'redo'],
                                ])
                                ->hidden(fn (?Page $record) => $record?->isHome()),
                        ]),
                    ])->columnSpan(['lg' => 2]),
                    Group::make([
                        Section::make('Publicación')->schema([
                            Toggle::make('is_published')
                                ->label(fn (?Page $record) => $record?->isHome() ? 'Usar estos bloques en la portada' : 'Publicada')
                                ->helperText(fn (?Page $record) => $record?->isHome() ? 'Si lo desactivas, la tienda muestra el diseño original de Stitch.' : null),
                            Toggle::make('show_in_footer')->label('Enlace en el pie de página')->default(true)
                                ->hidden(fn (?Page $record) => $record?->isHome()),
                            Select::make('type')->label('Tipo')
                                ->options([Page::TYPE_LEGAL => 'Texto legal', Page::TYPE_INFO => 'Información'])
                                ->default(Page::TYPE_INFO)->required()
                                ->hidden(fn (?Page $record) => $record?->isHome()),
                        ]),
                        Section::make('Datos automáticos')
                            ->hidden(fn (?Page $record) => $record?->isHome())
                            ->description('Escribe estas etiquetas en el texto y se sustituirán por los datos de la tienda.')
                            ->collapsed()
                            ->schema([
                                TextEntry::make('placeholders')->hiddenLabel()
                                    ->state(implode("\n", array_keys(Page::PLACEHOLDERS)))
                                    ->listWithLineBreaks(),
                            ]),
                        Section::make('Buscadores (SEO)')->collapsed()->schema([
                            TextInput::make('slug')->label('Dirección web')->prefix('/pagina/')->maxLength(150)
                                ->notIn([Page::HOME_SLUG])
                                ->hidden(fn (?Page $record) => $record?->isHome()),
                            Textarea::make('seo_description')->label('Descripción para Google')->rows(3)->maxLength(160),
                        ]),
                    ])->columnSpan(['lg' => 1]),
                ]),
                Section::make('Diseño de la página')
                    ->description(fn (?Page $record) => $record?->isHome()
                        ? 'Los bloques de la portada, en orden. Pulsa un bloque para editarlo; usa las flechas para moverlo.'
                        : 'Bloques opcionales que se muestran debajo del texto (portada, productos, banners...).')
                    ->columnSpanFull()
                    ->schema([PageBlocks::builder()]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')->label('Título')->searchable()
                    ->description(fn (Page $record) => match (true) {
                        $record->isHome() => 'Portada de la tienda',
                        $record->type === Page::TYPE_LEGAL => 'Texto legal',
                        default => null,
                    }),
                TextColumn::make('blocks')->label('Bloques')
                    ->state(fn (Page $record) => count($record->blocks ?? []))
                    ->badge(),
                IconColumn::make('is_published')->label('Publicada')->boolean(),
                IconColumn::make('show_in_footer')->label('En el pie')->boolean(),
                TextColumn::make('updated_at')->label('Última modificación')->since(),
            ])
            ->filters([TrashedFilter::make()->label('Papelera')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Enviar a la papelera')->hidden(fn (Page $record) => $record->isHome()),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
