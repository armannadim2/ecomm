<?php

namespace App\Filament\Resources\Returns;

use App\Domain\Orders\Enums\ReturnReason;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Enums\ReturnType;
use App\Domain\Orders\Models\ReturnRequest;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Returns\Pages\ListReturnRequests;
use App\Filament\Resources\Returns\Pages\ViewReturnRequest;
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
 * Returns and exchanges asked for by customers (backlog ORD-04). Staff approve or reject them and mark them
 * received and refunded/exchanged from the request page; every change e-mails the customer.
 */
class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?string $modelLabel = 'devolución';

    protected static ?string $pluralModelLabel = 'devoluciones y cambios';

    /** Badge: requests waiting for a decision. */
    public static function getNavigationBadge(): ?string
    {
        $waiting = ReturnRequest::query()->where('status', ReturnStatus::Requested->value)->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number'];
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
                                TextEntry::make('quantity')->label('Cant.'),
                                TextEntry::make('unit_price_cents')->label('Precio')->formatStateUsing(fn ($state) => Money::format($state)),
                                TextEntry::make('total')->label('Importe')->state(fn ($record) => Money::format($record->totalCents()))->weight('bold'),
                            ])
                            ->columns(4),
                    ]),
                    Section::make('Motivo del cliente')->schema([
                        TextEntry::make('reason')->label('Motivo')->formatStateUsing(fn (ReturnReason $state) => $state->getLabel()),
                        TextEntry::make('comment')->label('Comentario')->placeholder('—'),
                        TextEntry::make('rejection_reason')->label('Motivo del rechazo')
                            ->visible(fn (ReturnRequest $record) => $record->status === ReturnStatus::Rejected),
                        TextEntry::make('staff_note')->label('Nota interna')->placeholder('—'),
                    ]),
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Solicitud')->schema([
                        TextEntry::make('status')->label('Estado')->badge(),
                        TextEntry::make('type')->label('Tipo')->formatStateUsing(fn (ReturnType $state) => $state->getLabel()),
                        TextEntry::make('refund_cents')->label('Importe de los artículos')->formatStateUsing(fn ($state) => Money::format($state))->weight('bold'),
                        TextEntry::make('requested_at')->label('Solicitada')->dateTime('d/m/Y H:i'),
                        TextEntry::make('approved_at')->label('Aprobada')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('received_at')->label('Recibida')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('resolved_at')->label('Resuelta')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),
                    Section::make('Pedido y cliente')->schema([
                        TextEntry::make('order.number')->label('Pedido')
                            ->url(fn (ReturnRequest $record) => auth()->user()->can('view', $record->order) ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                        TextEntry::make('order.completed_at')->label('Entregado')->dateTime('d/m/Y'),
                        TextEntry::make('order.first_name')->label('Cliente')->formatStateUsing(fn (ReturnRequest $record) => $record->order->fullName()),
                        TextEntry::make('order.email')->label('Correo')->copyable(),
                        TextEntry::make('order.phone')->label('Teléfono')->copyable(),
                    ]),
                ])->columnSpan(['lg' => 1]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order')->withSum('items', 'quantity'))
            ->columns([
                TextColumn::make('number')->label('Solicitud')->searchable()->weight('bold'),
                TextColumn::make('order.number')->label('Pedido')->searchable(),
                TextColumn::make('order.last_name')->label('Cliente')
                    ->formatStateUsing(fn (ReturnRequest $record) => $record->order->fullName())
                    ->description(fn (ReturnRequest $record) => $record->order->email),
                TextColumn::make('type')->label('Tipo')->formatStateUsing(fn (ReturnType $state) => $state->getLabel()),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('items_sum_quantity')->label('Uds.')->alignCenter(),
                TextColumn::make('refund_cents')->label('Importe')->formatStateUsing(fn ($state) => Money::format($state))->sortable()->alignEnd(),
                TextColumn::make('requested_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(ReturnStatus::class)->multiple(),
                SelectFilter::make('type')->label('Tipo')->options(ReturnType::class),
            ])
            ->recordActions([ViewAction::make()])
            ->emptyStateHeading('Todavía no hay devoluciones')
            ->emptyStateDescription('Cuando un cliente pida devolver o cambiar un pedido entregado, aparecerá aquí.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturnRequests::route('/'),
            'view' => ViewReturnRequest::route('/{record}'),
        ];
    }
}
