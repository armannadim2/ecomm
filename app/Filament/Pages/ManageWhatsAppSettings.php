<?php

namespace App\Filament\Pages;

use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Filament\Support\ConfirmPassword;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * "Ajustes de WhatsApp": Meta WhatsApp Cloud API connection and the AI assistant.
 * Secrets are never sent back to the browser: an empty field keeps the saved value.
 */
class ManageWhatsAppSettings extends SettingsPage
{
    private const SECRETS = ['access_token', 'app_secret', 'anthropic_api_key'];

    protected static string $settings = WhatsAppSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al cliente';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ajustes de WhatsApp';

    protected static ?string $title = 'Ajustes de WhatsApp';

    protected static ?string $slug = 'ajustes-whatsapp';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('whatsapp.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        $saved = app(WhatsAppSettings::class);
        $secretHelp = fn (string $key) => filled($saved->{$key}) ? 'Guardado. Déjalo vacío para mantenerlo.' : 'Todavía no está guardado.';

        return $schema->columns(1)->components([
            Section::make('Conexión con WhatsApp (Meta)')
                ->description('Datos de tu app en developers.facebook.com → WhatsApp. El número no puede estar a la vez en la app WhatsApp Business del móvil, salvo con la función de coexistencia de Meta.')
                ->schema([
                    Toggle::make('enabled')->label('Recibir y responder mensajes de WhatsApp'),
                    TextInput::make('phone_number_id')->label('Phone number ID')->maxLength(40),
                    TextInput::make('access_token')->label('Token de acceso permanente')->password()->revealable()
                        ->helperText($secretHelp('access_token')),
                    TextInput::make('app_secret')->label('App secret')->password()->revealable()
                        ->helperText($secretHelp('app_secret').' Sirve para comprobar que los avisos vienen de Meta.'),
                    TextInput::make('verify_token')->label('Verify token')->maxLength(100)
                        ->helperText('Inventa una palabra y escríbela también en Meta al configurar el webhook.'),
                    Text::make('Dirección del webhook para Meta: '.route('webhooks.whatsapp')),
                ]),
            Section::make('Asistente con inteligencia artificial')
                ->description('Responde al momento con los datos de la tienda, los productos y el estado de los pedidos, y pasa a una persona las quejas, devoluciones y lo que no sepa.')
                ->schema([
                    Toggle::make('agent_enabled')->label('El asistente responde')
                        ->helperText('Si lo desactivas, todos los mensajes esperan a una persona del equipo.'),
                    TextInput::make('anthropic_api_key')->label('Clave API de Anthropic')->password()->revealable()
                        ->helperText($secretHelp('anthropic_api_key')),
                    Select::make('model')->label('Modelo')->required()->options([
                        'claude-opus-5-5' => 'Claude Opus 5.5 (recomendado)',
                        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 (más económico)',
                        'claude-haiku-5-5' => 'Claude Haiku 5.5 (el más económico)',
                    ]),
                    Textarea::make('instructions')->label('Indicaciones para el asistente (opcional)')->rows(4)->maxLength(3000)
                        ->placeholder('Ej.: Esta semana hay un 10 % en menaje. No prometas plazos de entrega.'),
                    Textarea::make('handoff_message')->label('Mensaje al pasar con una persona')->rows(2)->required()->maxLength(500),
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
        $saved = app(WhatsAppSettings::class);

        foreach (self::SECRETS as $key) {
            if (blank($data[$key] ?? null)) {
                $data[$key] = $saved->{$key};
            }
        }

        return $data;
    }
}
