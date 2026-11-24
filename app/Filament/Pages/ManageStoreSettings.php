<?php

namespace App\Filament\Pages;

use App\Domain\Store\Settings\StoreSettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManageStoreSettings extends SettingsPage
{
    protected static string $settings = StoreSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?string $navigationLabel = 'Datos de la tienda';

    protected static ?string $title = 'Datos de la tienda';

    protected static ?string $slug = 'datos-tienda';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tu tienda')
                    ->description('Cómo te ven los clientes.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('store_name')->label('Nombre de la tienda')->required()->maxLength(100),
                        TextInput::make('tagline')->label('Eslogan')->maxLength(150)
                            ->helperText('Una frase corta. Ej.: «Moda sostenible hecha en España».'),
                        FileUpload::make('logo_path')->label('Logo')
                            ->image()->disk('public')->directory('store')->visibility('public')
                            ->maxSize(2048)->columnSpanFull(),
                    ]),
                Section::make('Datos legales')
                    ->description('La ley (LSSI) obliga a mostrarlos en la web. Aparecen en el Aviso legal y en las facturas.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('legal_name')->label('Nombre o razón social')->maxLength(150)
                            ->helperText('Tu nombre completo si eres autónomo, o el de la empresa (S.L.).'),
                        TextInput::make('nif')->label('NIF / CIF')->maxLength(20),
                        TextInput::make('address_line')->label('Dirección')->maxLength(200)->columnSpanFull(),
                        TextInput::make('postal_code')->label('Código postal')->maxLength(5)->regex('/^\d{5}$/'),
                        TextInput::make('city')->label('Población')->maxLength(100),
                        TextInput::make('province')->label('Provincia')->maxLength(100),
                        Textarea::make('registry_info')->label('Datos del Registro Mercantil')->rows(2)
                            ->helperText('Solo para sociedades. Ej.: «Inscrita en el R.M. de Madrid, Tomo X, Folio Y, Hoja M-Z».')
                            ->columnSpanFull(),
                    ]),
                Section::make('Contacto')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')->label('Correo de contacto')->email()->maxLength(150),
                        TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                    ]),
            ]);
    }
}
