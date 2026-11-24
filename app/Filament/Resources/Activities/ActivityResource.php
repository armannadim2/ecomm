<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Read-only audit trail: who changed what, and when.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'historial de actividad';

    protected static ?string $navigationLabel = 'Historial de actividad';

    private const LOGS = [
        'catalog' => 'Catálogo',
        'stock' => 'Stock',
        'staff' => 'Equipo',
        'auth' => 'Accesos',
    ];

    private const EVENTS = [
        'created' => 'Creado',
        'updated' => 'Modificado',
        'deleted' => 'Eliminado',
        'restored' => 'Restaurado',
        'login' => 'Inicio de sesión',
        'stock_adjusted' => 'Ajuste de stock',
        'permissions_updated' => 'Permisos actualizados',
        'mfa_reset' => '2 pasos restablecido',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')->label('Fecha')->dateTime(),
                TextEntry::make('causer.name')->label('Quién')->placeholder('Sistema'),
                TextEntry::make('description')->label('Acción')
                    ->formatStateUsing(fn (?string $state, Activity $record) => self::EVENTS[$record->event] ?? $state),
                TextEntry::make('subject_type')->label('Elemento')
                    ->formatStateUsing(fn (?string $state, Activity $record) => class_basename((string) $state).' #'.$record->subject_id),
                KeyValueEntry::make('properties.attributes')->label('Valores nuevos')->columnSpanFull(),
                KeyValueEntry::make('properties.old')->label('Valores anteriores')->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('causer'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
                TextColumn::make('causer.name')->label('Quién')->placeholder('Sistema'),
                TextColumn::make('log_name')->label('Área')->badge()
                    ->formatStateUsing(fn (?string $state) => self::LOGS[$state] ?? $state),
                TextColumn::make('event')->label('Acción')
                    ->formatStateUsing(fn (?string $state, Activity $record) => self::EVENTS[$state] ?? $record->description),
                TextColumn::make('subject_type')->label('Elemento')
                    ->formatStateUsing(fn (?string $state, Activity $record) => class_basename((string) $state).' #'.$record->subject_id),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Área')->options(self::LOGS),
                SelectFilter::make('event')->label('Acción')->options(self::EVENTS),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
