<?php

namespace App\Filament\Pages;

use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Settings\StorefrontContentSettings;
use App\Filament\Blocks\PageBlocks;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Cabecera y pie": editable texts of the storefront header and footer, in Spanish, Catalan and English.
 * Page content itself is edited in Contenido → Páginas (page builder).
 */
class ManageStorefrontContent extends SettingsPage
{
    protected static string $settings = StorefrontContentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cabecera y pie';

    protected static ?string $title = 'Cabecera y pie de página';

    protected static ?string $slug = 'cabecera-y-pie';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('pages.update') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Textos que aparecen en todas las páginas de la tienda. Escribe en español; si dejas catalán o inglés vacío, se muestra el español.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Ver la tienda')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('store.home'), shouldOpenInNewTab: true),
            Action::make('restoreDefaults')
                ->label('Restaurar textos originales')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Vuelven los textos del diseño de Stitch en los tres idiomas. Se perderán tus cambios en esta pantalla.')
                ->action(function (StorefrontContentSettings $settings) {
                    foreach (DefaultContent::layout() as $key => $value) {
                        $settings->{$key} = $value;
                    }
                    $settings->save();
                    $this->fillForm();
                    Notification::make()->success()->title('Textos restaurados')->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Barra superior (azul)')
                ->description('La franja de arriba del todo. Si hay una campaña activa, se muestra la campaña en su lugar.')
                ->collapsible()
                ->schema([
                    PageBlocks::texts([
                        'announcement.text' => ['Mensaje principal (negrita)', 'text', true],
                        'announcement.extra' => ['Segundo mensaje', 'text', false, 'Ej.: «Envío gratis en Barcelona > 25€ • Código:»'],
                        'announcement.pill' => ['Pastilla de la derecha', 'text', false, 'Ej.: «Tienda abierta hoy hasta 21:00 h». Vacío = no se muestra.'],
                    ]),
                    TextInput::make('announcement.code')->label('Código promocional')->maxLength(30)
                        ->helperText('Se muestra en un recuadro después del segundo mensaje. Vacío = sin código.'),
                ]),

            Section::make('Cabecera')
                ->collapsible()
                ->schema([
                    PageBlocks::texts([
                        'header.tagline' => ['Texto bajo el logo', 'text', false, 'Ej.: «Tienda · Online»'],
                        'header.store_pill' => ['Botón de la tienda física', 'text', false, 'Ej.: «Tienda Barcelona (Av. Meridiana)». Vacío = no se muestra.'],
                        'header.search_placeholder' => ['Texto de ejemplo del buscador'],
                    ]),
                    PageBlocks::url('header.store_pill_url', 'Enlace del botón de la tienda física')
                        ->helperText('Por defecto #contacto (dirección en el pie). También puedes usar [mapa].'),
                ]),

            Section::make('Ventajas del pie (tarjetas)')
                ->collapsible()
                ->schema([
                    Repeater::make('value_props')->hiddenLabel()
                        ->schema([
                            PageBlocks::icon('icon', 'Icono'),
                            PageBlocks::texts(['title' => ['Título', 'text', true], 'text' => ['Texto', 'textarea']]),
                        ])
                        ->maxItems(3)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state) => $state['title']['es'] ?? null)
                        ->addActionLabel('Añadir tarjeta'),
                ]),

            Section::make('Pie de página')
                ->collapsible()
                ->schema([
                    PageBlocks::texts([
                        'footer.about' => ['Texto bajo el logo', 'textarea'],
                        'footer.chip_location' => ['Pastilla de ubicación', 'text', false, 'Ej.: «Tienda en Barcelona (Av. Meridiana)»'],
                        'footer.copyright' => ['Línea de copyright', 'text', true, 'Usa [año] y [empresa] (nombre legal de «Datos de la tienda»).'],
                        'footer.payments_note' => ['Nota de pagos (abajo a la derecha)'],
                        'footer.carriers_note' => ['Nota de transportistas'],
                    ]),
                    TextInput::make('footer.chip_web')->label('Pastilla web')->maxLength(60)->helperText('Ej.: totymes.com. Vacío = no se muestra.'),
                    Repeater::make('footer.columns')->label('Columnas de enlaces')
                        ->helperText('La columna «Legal» se crea sola con las páginas marcadas «Enlace en el pie de página».')
                        ->schema([
                            PageBlocks::texts(['title' => ['Título de la columna', 'text', true]]),
                            Repeater::make('links')->label('Enlaces')
                                ->schema([
                                    PageBlocks::texts(['label' => ['Texto del enlace', 'text', true]]),
                                    PageBlocks::url('url', 'Enlace')->required(),
                                ])
                                ->collapsible()
                                ->collapsed()
                                ->itemLabel(fn (array $state) => $state['label']['es'] ?? null)
                                ->addActionLabel('Añadir enlace'),
                        ])
                        ->maxItems(3)
                        ->collapsible()
                        ->collapsed()
                        ->itemLabel(fn (array $state) => $state['title']['es'] ?? null)
                        ->addActionLabel('Añadir columna'),
                ]),
        ]);
    }
}
