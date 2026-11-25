<?php

namespace App\Filament\Resources\ApiClients;

use App\Domain\Integrations\ApiScopes;
use App\Domain\Integrations\Models\ApiClient;
use App\Filament\Resources\ApiClients\Pages\ManageApiClients;
use App\Filament\Support\ConfirmPassword;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Integraciones → Claves API: who may use the REST API (/api/v1) and what for. */
class ApiClientResource extends Resource
{
    protected static ?string $model = ApiClient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Integraciones';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'clave API';

    protected static ?string $pluralModelLabel = 'claves API';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(self::detailFields());
    }

    /** @return list<mixed> */
    public static function detailFields(): array
    {
        return [
            TextInput::make('name')
                ->label('Nombre de la integración')
                ->placeholder('Ej.: TPV tienda Meridiana')
                ->required()
                ->maxLength(120),
            Textarea::make('description')
                ->label('Notas')
                ->placeholder('Quién la usa, para qué, a quién llamar si falla…')
                ->rows(2)
                ->maxLength(1000),
        ];
    }

    /** Scope picker used when creating a key or changing its permissions. @return list<mixed> */
    public static function scopeFields(): array
    {
        return [
            Select::make('preset')
                ->label('Plantilla')
                ->options(collect(ApiScopes::presets())->map(fn ($p) => $p['label']))
                ->placeholder('Elegir permisos a mano')
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (?string $state, Set $set) => $state && $set('scopes', ApiScopes::presets()[$state]['scopes'])),
            CheckboxList::make('scopes')
                ->label('Qué puede hacer')
                ->options(ApiScopes::labels())
                ->required()
                ->columns(1)
                ->helperText('Da solo los permisos que la integración necesita.'),
            ConfirmPassword::field(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Integración')->searchable()->description(fn (ApiClient $r) => $r->description),
                TextColumn::make('scopes')
                    ->label('Permisos')
                    ->state(fn (ApiClient $r) => $r->scopes())
                    ->badge()
                    ->separator(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->state(fn (ApiClient $r) => $r->isActive() ? 'Activa' : 'Revocada')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Activa' ? 'success' : 'danger'),
                TextColumn::make('last_used_at')->label('Último uso')->since()->placeholder('Nunca')->sortable(),
                TextColumn::make('creator.name')->label('Creada por')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Creada')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                Action::make('scopes')
                    ->label('Permisos')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->color('gray')
                    ->visible(fn (ApiClient $r) => $r->isActive())
                    ->fillForm(fn (ApiClient $r) => ['scopes' => $r->scopes()])
                    ->schema(self::scopeFields())
                    ->action(function (ApiClient $record, array $data) {
                        $record->tokens()->update(['abilities' => json_encode(array_values($data['scopes']))]);
                        Notification::make()->success()->title('Permisos actualizados')->body('La clave sigue siendo la misma.')->send();
                    }),
                Action::make('regenerate')
                    ->label('Nueva clave')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (ApiClient $r) => $r->isActive())
                    ->requiresConfirmation()
                    ->modalDescription('La clave actual dejará de funcionar al momento. Tendrás que poner la nueva en la integración.')
                    ->schema([ConfirmPassword::field()])
                    ->action(fn (ApiClient $record) => self::showKey($record->issueKey($record->scopes())->plainTextToken)),
                Action::make('revoke')
                    ->label('Revocar')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (ApiClient $r) => $r->isActive())
                    ->requiresConfirmation()
                    ->modalDescription('La integración dejará de tener acceso al momento. No se puede deshacer (podrás crear otra clave).')
                    ->schema([ConfirmPassword::field()])
                    ->action(function (ApiClient $record) {
                        $record->revoke();
                        Notification::make()->success()->title('Clave revocada')->send();
                    }),
            ]);
    }

    /** The plain key is shown once; only its hash is stored. */
    public static function showKey(string $key): void
    {
        Notification::make()
            ->warning()
            ->persistent()
            ->title('Copia la clave ahora: no se volverá a mostrar')
            ->body("Clave API: {$key}\n\nÚsala en la cabecera «Authorization: Bearer …» de cada petición a ".url('/api/v1').'.')
            ->send();
    }

    public static function getPages(): array
    {
        return ['index' => ManageApiClients::route('/')];
    }
}
