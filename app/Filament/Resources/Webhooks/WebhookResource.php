<?php

namespace App\Filament\Resources\Webhooks;

use App\Domain\Integrations\Jobs\SendWebhook;
use App\Domain\Integrations\Models\Webhook;
use App\Domain\Integrations\SafeUrl;
use App\Domain\Integrations\WebhookEvents;
use App\Filament\Resources\Webhooks\Pages\ManageWebhooks;
use App\Filament\Support\ConfirmPassword;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/** Integraciones → Webhooks: the shop tells other systems when orders, products or stock change. */
class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static string|UnitEnum|null $navigationGroup = 'Integraciones';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'webhook';

    protected static ?string $pluralModelLabel = 'webhooks';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->placeholder('Ej.: Avisar al TPV de pedidos nuevos')->required()->maxLength(120),
            TextInput::make('url')
                ->label('Dirección (URL) que recibirá los avisos')
                ->placeholder('https://tpv.ejemplo.com/webhooks/totmes')
                ->required()
                ->url()
                ->maxLength(500)
                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                    if ($problem = SafeUrl::check((string) $value)) {
                        $fail($problem);
                    }
                }),
            CheckboxList::make('events')->label('Avisar cuando…')->options(WebhookEvents::labels())->required()->columns(2),
            Toggle::make('is_active')->label('Activo')->default(true)
                ->helperText('Se desactiva solo tras '.Webhook::MAX_FAILURES.' fallos seguidos.'),
            ConfirmPassword::field(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->description(fn (Webhook $r) => Str::limit($r->url, 60)),
                TextColumn::make('events')->label('Eventos')->badge()->separator(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                TextColumn::make('last_status')->label('Última respuesta')->placeholder('—')
                    ->description(fn (Webhook $r) => $r->last_error ? Str::limit($r->last_error, 60) : null),
                TextColumn::make('last_delivered_at')->label('Último envío correcto')->since()->placeholder('Nunca'),
                TextColumn::make('consecutive_failures')->label('Fallos seguidos')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('test')
                    ->label('Probar')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('gray')
                    ->action(function (Webhook $record) {
                        try {
                            SendWebhook::dispatchSync($record->id, [
                                'id' => (string) Str::ulid(),
                                'event' => 'ping',
                                'created_at' => now()->toIso8601String(),
                                'data' => ['message' => 'Prueba desde el panel de '.config('app.name')],
                            ]);
                            Notification::make()->success()->title('Enviado: el servidor respondió '.$record->fresh()->last_status)->send();
                        } catch (Throwable) {
                            Notification::make()->danger()->title('No se pudo entregar')->body($record->fresh()->last_error)->send();
                        }
                    }),
                Action::make('secret')
                    ->label('Secreto')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('El secreto sirve al sistema receptor para comprobar la firma X-TotMes-Signature de cada aviso.')
                    ->schema([ConfirmPassword::field()])
                    ->action(fn (Webhook $record) => self::showSecret($record)),
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Borrar')->schema([ConfirmPassword::field()]),
            ]);
    }

    public static function showSecret(Webhook $webhook): void
    {
        Notification::make()
            ->warning()
            ->persistent()
            ->title('Secreto del webhook «'.$webhook->name.'»')
            ->body($webhook->secret."\n\nFirma: X-TotMes-Signature = sha256=HMAC-SHA256(cuerpo, secreto).")
            ->send();
    }

    public static function getPages(): array
    {
        return ['index' => ManageWebhooks::route('/')];
    }
}
