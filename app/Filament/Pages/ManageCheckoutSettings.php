<?php

namespace App\Filament\Pages;

use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Payments\Settings\PaymentSettings;
use App\Filament\Blocks\PageBlocks;
use App\Filament\Support\MoneyInput;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Envíos y pagos": Click & Collect, home delivery zones and prices (Peninsula, Balearics, the shop's
 * own local round), optional prices by weight and the texts shown at checkout.
 */
class ManageCheckoutSettings extends SettingsPage
{
    protected static string $settings = CheckoutSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Envíos y pagos';

    protected static ?string $title = 'Envíos y pagos';

    protected static ?string $slug = 'envios-y-pagos';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $online = app(PaymentSettings::class)->anyOnlineReady();

        return $schema->columns(1)->components([
            Section::make('Recogida en tienda (Click & Collect)')
                ->schema([
                    Toggle::make('pickup_enabled')->label('Ofrecer recogida en tienda'),
                    Toggle::make('pay_in_store_enabled')->label('Permitir pagar en la tienda al recoger'),
                    PageBlocks::texts([
                        'pickup_note' => ['Plazo de recogida', 'text', false, 'Ej.: «Listo en 2 horas en horario de tienda».'],
                        'opening_hours' => ['Horario de la tienda', 'text', false, 'Ej.: «Lunes a domingo: 10:00 - 21:00 h».'],
                    ]),
                ]),
            Section::make('Envío a domicilio')
                ->description($online
                    ? 'Se paga online (tarjeta, Bizum o PayPal). Canarias, Ceuta y Melilla no se sirven.'
                    : 'Necesita al menos un pago online activo en «Pagos online». Mientras tanto se muestra como «Próximamente».')
                ->columns(2)
                ->schema([
                    Toggle::make('delivery_enabled')->label('Ofrecer envío a domicilio')->columnSpanFull(),
                    MoneyInput::make('delivery_cost_cents')->label('Península (IVA incl.)')->required(),
                    MoneyInput::make('balearics_cost_cents')->label('Baleares (IVA incl.)')->required(),
                    MoneyInput::make('free_delivery_threshold_cents')->label('Envío gratis desde (0 = nunca)')->required(),
                    Toggle::make('balearics_free_delivery')->label('El envío gratis también vale para Baleares')->inline(false),
                    Repeater::make('weight_rates')
                        ->label('Precios por peso (opcional)')
                        ->helperText('Si añades filas, el precio depende del peso del paquete (peso de cada variante × cantidad) en lugar de los precios de arriba. Ordénalas de menos a más kilos; los paquetes más pesados pagan la última fila.')
                        ->columnSpanFull()
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Añadir tramo de peso')
                        ->schema([
                            TextInput::make('max_kg')->label('Hasta (kg)')->numeric()->minValue(0.1)->step('0.1')->required(),
                            MoneyInput::make('peninsula_cents')->label('Península')->required(),
                            MoneyInput::make('balearics_cents')->label('Baleares')->required(),
                        ]),
                ]),
            Section::make('Entrega local (reparto propio)')
                ->description('Para los códigos postales donde repartís vosotros mismos, con su propio precio.')
                ->columns(2)
                ->schema([
                    Toggle::make('local_delivery_enabled')->label('Ofrecer entrega local')->columnSpanFull(),
                    Textarea::make('local_delivery_postcodes')->label('Códigos postales')->rows(2)->maxLength(2000)->columnSpanFull()
                        ->helperText('Separados por comas. Rangos «08001-08042», códigos exactos «08901» o prefijos «0819».'),
                    MoneyInput::make('local_delivery_cost_cents')->label('Precio de la entrega local (IVA incl.)')->required(),
                    PageBlocks::texts([
                        'local_delivery_note' => ['Texto de la entrega local', 'text', false, 'Ej.: «Te lo llevamos en 24 h».'],
                    ])->columnSpanFull(),
                ]),
        ]);
    }
}
