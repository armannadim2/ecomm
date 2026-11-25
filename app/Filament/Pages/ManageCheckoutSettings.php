<?php

namespace App\Filament\Pages;

use App\Domain\Orders\Settings\CheckoutSettings;
use App\Filament\Blocks\PageBlocks;
use App\Filament\Support\MoneyInput;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** "Envíos y pagos": delivery options, prices and the texts shown at checkout. */
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
                ->description('Necesita pago online (Bizum, tarjeta o PayPal), que todavía no está conectado. Mientras tanto la opción se muestra como «Próximamente».')
                ->columns(2)
                ->schema([
                    Toggle::make('delivery_enabled')->label('Ofrecer envío a domicilio')->columnSpanFull()
                        ->disabled()
                        ->helperText('Se activará cuando el pago online esté conectado.'),
                    MoneyInput::make('delivery_cost_cents')->label('Precio del envío (IVA incl.)')->required(),
                    MoneyInput::make('free_delivery_threshold_cents')->label('Envío gratis desde (0 = nunca)')->required(),
                ]),
        ]);
    }
}
