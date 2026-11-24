<?php

namespace App\Filament\Resources\Staff;

use App\Domain\Staff\Models\Staff;
use App\Filament\Resources\Staff\Pages\ManageStaff;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Equipo';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'empleado';

    protected static ?string $pluralModelLabel = 'empleados';

    protected static ?string $navigationLabel = 'Empleados';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->helperText(fn (string $operation) => $operation === 'edit'
                        ? 'Déjalo vacío para mantener la contraseña actual.'
                        : 'La persona deberá activar la verificación en dos pasos en su primer acceso.'),
                Select::make('roles')
                    ->label('Rol')
                    ->helperText('Define qué puede ver y hacer en el panel.')
                    ->relationship(
                        'roles',
                        'name',
                        fn (Builder $query) => $query
                            ->where('guard_name', Staff::GUARD)
                            // Only an owner can make someone else an owner.
                            ->when(! auth()->user()?->isOwner(), fn ($q) => $q->where('name', '!=', Staff::OWNER_ROLE)),
                    )
                    ->multiple()
                    ->maxItems(1)
                    ->preload()
                    ->required(),
                Toggle::make('is_active')
                    ->label('Puede acceder al panel')
                    ->helperText('Desactívalo para bloquear el acceso al momento sin borrar la cuenta.')
                    ->default(true),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()
                    ->description(fn (Staff $record) => $record->email),
                TextColumn::make('roles.name')->label('Rol')->badge(),
                IconColumn::make('is_active')->label('Acceso')->boolean(),
                IconColumn::make('mfa')
                    ->label('Verificación en dos pasos')
                    ->state(fn (Staff $record) => filled($record->app_authentication_secret))
                    ->boolean(),
                TextColumn::make('last_login_at')->label('Último acceso')->since()->placeholder('Nunca')->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Eliminados'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetMfa')
                    ->label('Restablecer 2 pasos')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->visible(fn (Staff $record) => auth()->user()?->isOwner()
                        && filled($record->app_authentication_secret)
                        && ! $record->is(auth()->user()))
                    ->requiresConfirmation()
                    ->modalDescription('Úsalo si esta persona ha perdido el móvil. Tendrá que configurar de nuevo la verificación en dos pasos al entrar.')
                    ->action(function (Staff $record): void {
                        $record->saveAppAuthenticationSecret(null);
                        $record->saveAppAuthenticationRecoveryCodes(null);

                        activity('staff')->performedOn($record)->event('mfa_reset')->log('Verificación en dos pasos restablecida');

                        Notification::make()->success()->title('Verificación en dos pasos restablecida')->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaff::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
