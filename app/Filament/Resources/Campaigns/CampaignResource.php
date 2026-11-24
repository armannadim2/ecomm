<?php

namespace App\Filament\Resources\Campaigns;

use App\Domain\Marketing\Enums\CampaignEffect;
use App\Domain\Marketing\Enums\CampaignTheme;
use App\Domain\Marketing\Models\Campaign;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'campaña';

    protected static ?string $pluralModelLabel = 'campañas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('1. Elige la campaña')
                    ->description('Al elegir un tipo rellenamos el texto, el color y el efecto. Puedes cambiarlo todo.')
                    ->columns(2)
                    ->schema([
                        Select::make('theme')
                            ->label('Tipo de campaña')
                            ->options(CampaignTheme::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (?CampaignTheme $state, Set $set, Get $get): void {
                                if ($state === null) {
                                    return;
                                }
                                $defaults = $state->defaults();
                                $set('effect', $defaults['effect']);
                                $set('accent_color', $defaults['accent_color']);
                                $set('headline', $defaults['headline']);
                                $set('subheadline', $defaults['subheadline']);
                                $set('cta_label', $defaults['cta_label']);
                                if (blank($get('name'))) {
                                    $set('name', $state->getLabel().' '.now()->year);
                                }
                            }),
                        TextInput::make('name')->label('Nombre interno')->required()->maxLength(100)
                            ->helperText('Solo lo ves tú. Ej.: «Black Friday 2026».'),
                    ]),
                Section::make('2. Fechas')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')->label('Empieza')->required()->seconds(false)->native(false),
                        DateTimePicker::make('ends_at')->label('Termina')->required()->seconds(false)->native(false)
                            ->after('starts_at'),
                        Toggle::make('show_countdown')->label('Mostrar cuenta atrás hasta el final')->default(true),
                        Toggle::make('is_active')->label('Activada')->default(true)
                            ->helperText('Desactívala para pausarla sin borrarla.'),
                    ]),
                Section::make('3. Cómo se ve')
                    ->columns(2)
                    ->schema([
                        TextInput::make('headline')->label('Titular')->required()->maxLength(80),
                        TextInput::make('subheadline')->label('Subtítulo')->maxLength(160),
                        TextInput::make('cta_label')->label('Texto del botón')->maxLength(60),
                        TextInput::make('cta_url')->label('Enlace del botón')->maxLength(255)
                            ->helperText('Ej.: /categoria/ofertas')
                            ->regex('#^(/|https://)#'),
                        Select::make('effect')->label('Efecto en la portada')->options(CampaignEffect::class)
                            ->default(CampaignEffect::None)->required(),
                        ColorPicker::make('accent_color')->label('Color principal')->required()
                            ->regex('/^#[0-9a-fA-F]{6}$/'),
                        SpatieMediaLibraryFileUpload::make('banner')
                            ->label('Imagen de fondo (opcional)')
                            ->collection(Campaign::BANNER)
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Horizontal, al menos 1920 × 800 px.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Avanzado')
                    ->collapsed()
                    ->schema([
                        TextInput::make('priority')->label('Prioridad')->integer()->minValue(0)->default(0)
                            ->helperText('Si dos campañas coinciden en fechas, se muestra la de mayor prioridad.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                ColorColumn::make('accent_color')->label(''),
                TextColumn::make('name')->label('Campaña')->searchable()
                    ->description(fn (Campaign $record) => $record->theme->getLabel()),
                TextColumn::make('state')->label('Estado')->badge()
                    ->state(fn (Campaign $record) => $record->stateLabel())
                    ->color(fn (string $state) => match ($state) {
                        'En curso' => 'success',
                        'Programada' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')->label('Empieza')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('ends_at')->label('Termina')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('effect')->label('Efecto')->badge()->color('gray'),
            ])
            ->filters([TrashedFilter::make()->label('Papelera')])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Enviar a la papelera'),
                RestoreAction::make(),
            ])
            ->emptyStateHeading('Sin campañas')
            ->emptyStateDescription('Crea una campaña para Black Friday, Navidad o Rebajas y la portada cambiará sola en esas fechas.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
