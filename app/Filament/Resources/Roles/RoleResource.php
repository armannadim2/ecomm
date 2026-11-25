<?php

namespace App\Filament\Resources\Roles;

use App\Domain\Staff\Models\Staff;
use App\Domain\Staff\Permissions;
use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Filament\Support\ConfirmPassword;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Equipo';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'rol';

    protected static ?string $pluralModelLabel = 'roles';

    protected static ?string $navigationLabel = 'Roles y permisos';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('guard_name', Staff::GUARD);
    }

    public static function form(Schema $schema): Schema
    {
        $order = array_flip(Permissions::all());

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre del rol')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('guard_name', Staff::GUARD)),
                CheckboxList::make('permissions')
                    ->label('¿Qué puede hacer este rol?')
                    ->relationship(
                        'permissions',
                        'name',
                        fn (Builder $query) => $query->where('guard_name', Staff::GUARD),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => self::groupLabel($record->name).' · '.Permissions::label($record->name))
                    ->bulkToggleable()
                    ->searchable()
                    ->columns(2),
                ConfirmPassword::field(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rol')->searchable()
                    ->description(fn (Role $record) => $record->name === Staff::OWNER_ROLE ? 'Acceso total. No se puede modificar.' : null),
                TextColumn::make('permissions_count')->label('Permisos')->counts('permissions')
                    ->formatStateUsing(fn ($state, Role $record) => $record->name === Staff::OWNER_ROLE ? 'Todos' : $state),
                TextColumn::make('users_count')->label('Empleados')->counts('users'),
            ])
            ->recordActions([
                EditAction::make()->after(fn (Role $record) => self::afterPermissionsChanged($record)),
                DeleteAction::make()->schema([ConfirmPassword::field()]),
            ]);
    }

    public static function afterPermissionsChanged(Role $role): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('staff')
            ->performedOn($role)
            ->withProperties(['permissions' => $role->permissions()->pluck('name')->all()])
            ->event('permissions_updated')
            ->log('Permisos del rol actualizados');
    }

    private static function groupLabel(string $permission): string
    {
        foreach (Permissions::groups() as $group) {
            if (array_key_exists($permission, $group['permissions'])) {
                return $group['label'];
            }
        }

        return '';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
