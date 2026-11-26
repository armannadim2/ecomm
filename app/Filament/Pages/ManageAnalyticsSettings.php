<?php

namespace App\Filament\Pages;

use App\Domain\Marketing\Settings\AnalyticsSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Cookies y analítica": Google Analytics 4 and Meta pixel ids. They load only after the visitor
 * accepts them in the cookie banner; with both empty the banner is not shown (technical cookies only).
 */
class ManageAnalyticsSettings extends SettingsPage
{
    protected static string $settings = AnalyticsSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Cookies y analítica';

    protected static ?string $title = 'Cookies y analítica';

    protected static ?string $slug = 'cookies-y-analitica';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Etiquetas de analítica y publicidad')
                ->description('Solo se cargan si el visitante las acepta en el aviso de cookies. Antes de activarlas, actualiza la Política de cookies con estas herramientas.')
                ->schema([
                    TextInput::make('ga4_measurement_id')->label('Google Analytics 4 (ID de medición)')->placeholder('G-XXXXXXXXXX')
                        ->regex('/^G-[A-Z0-9]{4,20}$/')->maxLength(30),
                    TextInput::make('meta_pixel_id')->label('Píxel de Meta (Facebook / Instagram)')->regex('/^\d{6,20}$/')->maxLength(20),
                    TextInput::make('consent_version')->label('Versión de la política de cookies')->numeric()->integer()->minValue(1)->required()
                        ->helperText('Súbela en 1 cuando cambies la política: se volverá a pedir el consentimiento a todo el mundo.'),
                ]),
        ]);
    }
}
