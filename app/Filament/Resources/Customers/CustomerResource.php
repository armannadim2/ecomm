<?php

namespace App\Filament\Resources\Customers;

use App\Domain\Customers\Models\Customer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only customer lookup for support and order handling.
 * Customers edit or delete their own data from "Mi cuenta".
 */
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Clientes';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name')->label('Nombre')
                    ->formatStateUsing(fn (Customer $record) => $record->fullName()),
                TextEntry::make('email')->label('Correo')->copyable(),
                TextEntry::make('phone')->label('Teléfono')->placeholder('—'),
                TextEntry::make('created_at')->label('Cliente desde')->date(),
                TextEntry::make('email_verified_at')->label('Correo verificado')->date()->placeholder('No'),
                TextEntry::make('marketing_consent_at')->label('Acepta publicidad desde')->date()->placeholder('No'),
                RepeatableEntry::make('addresses')->label('Direcciones')
                    ->schema([
                        TextEntry::make('line1')->hiddenLabel()
                            ->formatStateUsing(fn ($record) => implode(' · ', $record->lines())),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('addresses'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')->label('Nombre')
                    ->formatStateUsing(fn (Customer $record) => $record->fullName())
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('phone')->label('Teléfono')->searchable()->placeholder('—'),
                IconColumn::make('email_verified_at')->label('Verificado')->boolean()
                    ->state(fn (Customer $record) => $record->email_verified_at !== null),
                IconColumn::make('marketing_consent_at')->label('Publicidad')->boolean()
                    ->state(fn (Customer $record) => $record->hasMarketingConsent()),
                TextColumn::make('created_at')->label('Alta')->date()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('marketing')->label('Acepta publicidad')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('marketing_consent_at'),
                        false: fn (Builder $q) => $q->whereNull('marketing_consent_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
        ];
    }
}
