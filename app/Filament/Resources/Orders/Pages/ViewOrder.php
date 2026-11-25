<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\URL;

/** Order page: one button per next status ("Empezar a preparar", "Listo para recoger"...) and a staff note. */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Pedido '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->getRecord();

        $statusActions = collect($order->status->next($order->fulfillment))
            ->map(fn (OrderStatus $to) => Action::make('to_'.$to->value)
                ->label(match ($to) {
                    OrderStatus::Confirmed => 'Confirmar',
                    OrderStatus::Preparing => 'Empezar a preparar',
                    OrderStatus::ReadyForPickup => 'Listo para recoger',
                    OrderStatus::Shipped => 'Marcar como enviado',
                    OrderStatus::Completed => 'Marcar como entregado',
                    OrderStatus::Cancelled => 'Cancelar pedido',
                    default => $to->getLabel(),
                })
                ->color($to === OrderStatus::Cancelled ? 'danger' : ($to === OrderStatus::Completed ? 'success' : 'primary'))
                ->requiresConfirmation($to === OrderStatus::Cancelled || $to === OrderStatus::Completed)
                ->modalDescription($to === OrderStatus::Cancelled ? 'Las unidades vuelven al stock. El cliente no recibe ningún aviso automático todavía.' : null)
                ->visible(fn () => auth()->user()->can('update', $order))
                ->action(function (ChangeOrderStatus $change) use ($order, $to) {
                    try {
                        $change->handle($order, $to);
                    } catch (DomainRuleException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }
                    Notification::make()->success()->title('Pedido: '.$to->getLabel())->send();
                    $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
                }))
            ->values()
            ->all();

        return [
            ...$statusActions,
            Action::make('note')
                ->label('Nota interna')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn () => auth()->user()->can('update', $order))
                ->fillForm(fn () => ['staff_note' => $order->staff_note])
                ->schema([Textarea::make('staff_note')->label('Nota (solo la ve el equipo)')->rows(4)->maxLength(2000)])
                ->action(fn (array $data) => $order->update(['staff_note' => $data['staff_note']])),
            Action::make('customerView')
                ->label('Ver como cliente')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => URL::temporarySignedRoute('order.show', now()->addHour(), $order), shouldOpenInNewTab: true),
            ActionGroup::make([
                Action::make('mailPlaced')
                    ->label('Correo de confirmación')
                    ->url(fn () => route('admin.order-mail.preview', [$order, 'confirmacion']), shouldOpenInNewTab: true),
                Action::make('mailReady')
                    ->label('Correo «Listo para recoger»')
                    ->visible($order->fulfillment === Fulfillment::Pickup)
                    ->url(fn () => route('admin.order-mail.preview', [$order, 'listo-para-recoger']), shouldOpenInNewTab: true),
            ])->label('Ver correos')->icon('heroicon-o-envelope')->color('gray')->button(),
        ];
    }
}
