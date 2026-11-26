<?php

namespace App\Filament\Resources\Orders;

use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\PaymentStatus;
use App\Domain\Orders\Enums\ShippingZone;
use App\Domain\Orders\Models\Order;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Orders placed in the shop. Staff follow them through their statuses (preparing, ready, shipped...)
 * from the order page. Orders are created only by customers at checkout.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'pedidos';

    /** Badge: open orders that need attention. */
    public static function getNavigationBadge(): ?string
    {
        $open = Order::query()->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::PendingPayment])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'pickup_code', 'email', 'last_name'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Group::make([
                    Section::make('Artículos')->schema([
                        RepeatableEntry::make('items')->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name')->label('Producto')
                                    ->formatStateUsing(fn ($record) => $record->product_name.($record->variant_name ? ' · '.$record->variant_name : '')),
                                TextEntry::make('sku')->label('Ref.'),
                                TextEntry::make('quantity')->label('Cant.'),
                                TextEntry::make('unit_price_cents')->label('Precio')->formatStateUsing(fn ($state) => Money::format($state)),
                                TextEntry::make('line_total_cents')->label('Importe')->formatStateUsing(fn ($state) => Money::format($state))->weight('bold'),
                            ])
                            ->columns(5),
                    ]),
                    Section::make('Importes')->columns(4)->schema([
                        TextEntry::make('subtotal_cents')->label('Subtotal')->formatStateUsing(fn ($state) => Money::format($state)),
                        TextEntry::make('shipping_cents')->label('Envío')->formatStateUsing(fn ($state) => $state ? Money::format($state) : 'Gratis'),
                        TextEntry::make('tax_cents')->label('IVA incluido')->formatStateUsing(fn ($state) => Money::format($state)),
                        TextEntry::make('total_cents')->label('Total')->formatStateUsing(fn ($state) => Money::format($state))->weight('bold')->size('lg'),
                        TextEntry::make('refunded_cents')->label('Devuelto al cliente')->formatStateUsing(fn ($state) => Money::format($state))
                            ->color('danger')->visible(fn (Order $record) => $record->refunded_cents > 0),
                    ]),
                    Section::make('Pagos online')
                        ->visible(fn (Order $record) => $record->payments()->exists())
                        ->schema([
                            RepeatableEntry::make('payments')->hiddenLabel()->columns(5)->schema([
                                TextEntry::make('created_at')->label('Intento')->dateTime('d/m/Y H:i'),
                                TextEntry::make('method')->label('Método'),
                                TextEntry::make('status')->label('Resultado')->badge(),
                                TextEntry::make('amount_cents')->label('Importe')->formatStateUsing(fn ($state) => Money::format($state)),
                                TextEntry::make('failure_reason')->label('Detalle')->placeholder('—')
                                    ->formatStateUsing(fn ($state, $record) => $state ?: ($record->transaction_id ? 'Ref. '.$record->transaction_id : null)),
                            ]),
                        ]),
                    Section::make('Reembolsos')
                        ->visible(fn (Order $record) => $record->refunds()->exists())
                        ->schema([
                            RepeatableEntry::make('refunds')->hiddenLabel()->columns(4)->schema([
                                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                                TextEntry::make('amount_cents')->label('Importe')->formatStateUsing(fn ($state) => Money::format($state)),
                                TextEntry::make('channel')->label('Cómo')->formatStateUsing(fn ($state) => $state === 'gateway' ? 'Devuelto por la pasarela' : 'Devuelto a mano'),
                                TextEntry::make('reason')->label('Motivo'),
                            ]),
                        ]),
                    Section::make('Facturas')
                        ->visible(fn (Order $record) => $record->invoices()->exists())
                        ->schema([
                            RepeatableEntry::make('invoices')->hiddenLabel()->columns(4)->schema([
                                TextEntry::make('code')->label('Número')->weight('bold')
                                    ->url(fn ($record) => route('admin.invoice', $record), shouldOpenInNewTab: true),
                                TextEntry::make('type')->label('Tipo'),
                                TextEntry::make('issued_at')->label('Fecha')->dateTime('d/m/Y'),
                                TextEntry::make('total_cents')->label('Total')->formatStateUsing(fn ($state) => Money::format($state)),
                            ]),
                        ]),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Estado')->schema([
                        TextEntry::make('status')->label('Estado')->badge(),
                        TextEntry::make('payment_status')->label('Pago')->badge()
                            ->formatStateUsing(fn (Order $record) => $record->payment_status->getLabel().' · '.$record->payment_method->getLabel()),
                        TextEntry::make('fulfillment')->label('Entrega'),
                        TextEntry::make('pickup_code')->label('Código de recogida')->copyable()->weight('bold')
                            ->visible(fn (Order $record) => $record->fulfillment === Fulfillment::Pickup),
                        TextEntry::make('placed_at')->label('Realizado')->dateTime('d/m/Y H:i'),
                        TextEntry::make('payment_expires_at')->label('Reserva hasta')->dateTime('d/m/Y H:i')
                            ->visible(fn (Order $record) => $record->isAwaitingOnlinePayment()),
                        TextEntry::make('shipping_zone')->label('Zona de envío')
                            ->formatStateUsing(fn ($state) => ShippingZone::tryFrom((string) $state)?->getLabel())
                            ->visible(fn (Order $record) => filled($record->shipping_zone)),
                        TextEntry::make('tracking_number')->label('Seguimiento')
                            ->formatStateUsing(fn (Order $record) => trim($record->carrier.' '.$record->tracking_number))
                            ->url(fn (Order $record) => $record->tracking_url, shouldOpenInNewTab: true)
                            ->visible(fn (Order $record) => filled($record->tracking_number)),
                        TextEntry::make('cancel_reason')->label('Motivo de la cancelación')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'payment_expired' => 'No llegó el pago a tiempo',
                                'customer' => 'Cancelado por el cliente',
                                default => 'Cancelado por el equipo',
                            })
                            ->visible(fn (Order $record) => $record->status === OrderStatus::Cancelled),
                    ]),
                    Section::make('Cliente')->schema([
                        TextEntry::make('first_name')->label('Nombre')->formatStateUsing(fn (Order $record) => $record->fullName()),
                        TextEntry::make('email')->label('Correo')->copyable(),
                        TextEntry::make('phone')->label('Teléfono')->copyable(),
                        TextEntry::make('tax_id')->label('NIF / DNI')->placeholder('—'),
                        TextEntry::make('ship_line1')->label('Dirección de entrega')
                            ->formatStateUsing(fn (Order $record) => implode(' · ', $record->shippingLines()))
                            ->visible(fn (Order $record) => $record->fulfillment === Fulfillment::Delivery),
                        TextEntry::make('customer_note')->label('Nota del cliente')->placeholder('—'),
                        TextEntry::make('marketing_opt_in')->label('Acepta publicidad')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('items', 'quantity'))
            ->columns([
                TextColumn::make('number')->label('Pedido')->searchable()->weight('bold')
                    ->description(fn (Order $record) => $record->pickup_code),
                TextColumn::make('last_name')->label('Cliente')
                    ->formatStateUsing(fn (Order $record) => $record->fullName())
                    ->description(fn (Order $record) => $record->email)
                    ->searchable(['first_name', 'last_name', 'email', 'phone']),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('fulfillment')->label('Entrega'),
                TextColumn::make('payment_status')->label('Pago')->badge(),
                TextColumn::make('items_sum_quantity')->label('Uds.')->alignCenter(),
                TextColumn::make('total_cents')->label('Total')->formatStateUsing(fn ($state) => Money::format($state))->sortable()->alignEnd(),
                TextColumn::make('placed_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(OrderStatus::class)->multiple(),
                SelectFilter::make('fulfillment')->label('Entrega')->options(Fulfillment::class),
                SelectFilter::make('payment_status')->label('Pago')->options(PaymentStatus::class),
            ])
            ->recordActions([ViewAction::make()])
            ->emptyStateHeading('Todavía no hay pedidos')
            ->emptyStateDescription('Cuando un cliente compre en la tienda, su pedido aparecerá aquí.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
