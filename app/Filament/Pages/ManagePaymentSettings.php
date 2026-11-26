<?php

namespace App\Filament\Pages;

use App\Domain\Payments\Settings\PaymentSettings;
use App\Filament\Support\ConfirmPassword;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Pagos online": Redsys (cards + Bizum) and PayPal credentials. Secrets are stored encrypted and
 * never sent back to the browser: an empty field keeps the saved value.
 * A method appears at checkout only when it is switched on AND its credentials are complete.
 */
class ManagePaymentSettings extends SettingsPage
{
    private const SECRETS = ['redsys_secret_key', 'paypal_client_secret'];

    protected static string $settings = PaymentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Pagos online';

    protected static ?string $title = 'Pagos online';

    protected static ?string $slug = 'pagos-online';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $saved = app(PaymentSettings::class);
        $secretHelp = fn (string $key) => filled($saved->{$key}) ? 'Guardado. Déjalo vacío para mantenerlo.' : 'Todavía no está guardado.';
        $state = fn (bool $ready) => $ready ? '✅ Activo en la tienda' : '⏸️ No se muestra a los clientes';

        return $schema->columns(1)->components([
            Section::make('Redsys: tarjeta y Bizum')
                ->description('Datos del TPV virtual que te da tu banco (Canales → Administración del comercio de Redsys).')
                ->columns(2)
                ->schema([
                    Toggle::make('redsys_card_enabled')->label('Aceptar tarjeta')->helperText($state($saved->cardReady())),
                    Toggle::make('redsys_bizum_enabled')->label('Aceptar Bizum')->helperText($state($saved->bizumReady()).' · Bizum tiene que estar activado en tu terminal.'),
                    Toggle::make('redsys_test_mode')->label('Modo pruebas (entorno de test de Redsys)')->columnSpanFull()
                        ->helperText('Con el modo pruebas no se cobra dinero real. Desactívalo solo con los datos del terminal real.'),
                    TextInput::make('redsys_merchant_code')->label('Número de comercio (FUC)')->maxLength(12)->regex('/^\d{6,12}$/'),
                    TextInput::make('redsys_terminal')->label('Terminal')->required()->maxLength(3)->regex('/^\d{1,3}$/'),
                    TextInput::make('redsys_secret_key')->label('Clave secreta SHA-256')->password()->revealable()->columnSpanFull()
                        ->maxLength(100)->helperText($secretHelp('redsys_secret_key')),
                    Text::make('URL de notificación (la pide el banco): '.route('webhooks.redsys'))->columnSpanFull(),
                ]),
            Section::make('PayPal')
                ->description('Crea una app en developer.paypal.com → Apps & Credentials y copia el Client ID y el Secret.')
                ->columns(2)
                ->schema([
                    Toggle::make('paypal_enabled')->label('Aceptar PayPal')->helperText($state($saved->paypalReady())),
                    Toggle::make('paypal_sandbox')->label('Modo pruebas (Sandbox)')
                        ->helperText('En Sandbox no se cobra dinero real. Usa las credenciales «Live» para la tienda real.'),
                    TextInput::make('paypal_client_id')->label('Client ID')->maxLength(200)->columnSpanFull(),
                    TextInput::make('paypal_client_secret')->label('Secret')->password()->revealable()->maxLength(200)->columnSpanFull()
                        ->helperText($secretHelp('paypal_client_secret')),
                    TextInput::make('paypal_webhook_id')->label('Webhook ID')->maxLength(60)->columnSpanFull()
                        ->helperText('Crea un webhook en PayPal con la dirección de abajo y los eventos «Checkout order approved», «Payment capture completed» y «Payment capture denied».'),
                    Text::make('Dirección del webhook para PayPal: '.route('webhooks.paypal'))->columnSpanFull(),
                ]),
            Section::make('Reserva de stock')
                ->schema([
                    TextInput::make('reservation_minutes')->label('Minutos que se guardan los productos mientras el cliente paga')
                        ->numeric()->integer()->minValue(10)->maxValue(120)->required()
                        ->helperText('Si no llega el pago en ese tiempo (más 15 minutos de margen), el pedido se cancela y el stock vuelve a la venta.'),
                ]),
            ConfirmPassword::field(),
        ]);
    }

    /** Secrets are not shown again. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (self::SECRETS as $key) {
            $data[$key] = null;
        }

        return $data;
    }

    /** An empty secret field keeps the saved secret. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $saved = app(PaymentSettings::class);

        foreach (self::SECRETS as $key) {
            $data[$key] = blank($data[$key] ?? null) ? $saved->{$key} : trim((string) $data[$key]);
        }

        return $data;
    }
}
