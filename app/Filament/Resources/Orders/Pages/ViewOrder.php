<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Domain\Orders\Actions\ChangeOrderStatus;
use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Actions\RefundOrder;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Support\ConfirmPassword;
use App\Filament\Support\MoneyInput;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                ->modalDescription($to === OrderStatus::Cancelled
                    ? ($order->payment_status->isPaid()
                        ? 'Las unidades vuelven al stock. El pedido ya está pagado: devuelve el dinero con «Reembolsar».'
                        : 'Las unidades vuelven al stock.')
                    : null)
                ->schema($to === OrderStatus::Shipped ? [
                    TextInput::make('carrier')->label('Empresa de transporte')->maxLength(60)->placeholder('Correos, SEUR, GLS…'),
                    TextInput::make('tracking_number')->label('Número de seguimiento')->maxLength(80),
                    TextInput::make('tracking_url')->label('Enlace de seguimiento')->url()->maxLength(255)->placeholder('https://…'),
                ] : null)
                ->modalSubmitActionLabel($to === OrderStatus::Shipped ? 'Marcar como enviado y avisar al cliente' : null)
                ->visible(fn () => auth()->user()->can('update', $order))
                ->action(function (ChangeOrderStatus $change, array $data = []) use ($order, $to) {
                    try {
                        $change->handle($order, $to, shipping: $data);
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
            Action::make('refund')
                ->label('Reembolsar')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->visible(fn () => auth()->user()->can('orders.refund') && $order->refundableCents() > 0)
                ->modalHeading('Devolver dinero al cliente')
                ->modalDescription(fn () => $order->payment_method->isOnline()
                    ? 'El dinero vuelve a la misma tarjeta, Bizum o cuenta PayPal. Se emite una factura rectificativa y se avisa al cliente por correo.'
                    : 'Pedido pagado en la tienda: devuelve el dinero en el mostrador y regístralo aquí. Se emite una factura rectificativa.')
                ->schema([
                    MoneyInput::make('amount_cents')->label('Importe a devolver')->required()
                        ->default($order->refundableCents())
                        ->helperText('Máximo '.Money::format($order->refundableCents()).'.'),
                    Textarea::make('reason')->label('Motivo (sale en la factura rectificativa)')->required()->maxLength(500)->rows(2),
                    Toggle::make('manual')->label('Ya lo he devuelto yo (efectivo o transferencia)')
                        ->visible($order->payment_method->isOnline())
                        ->helperText('Marca esto solo si NO quieres que el dinero salga por la pasarela.'),
                    ConfirmPassword::field(),
                ])
                ->action(function (array $data, RefundOrder $refund) use ($order) {
                    try {
                        $refund->handle($order, (int) $data['amount_cents'], (string) $data['reason'], auth()->user(), (bool) ($data['manual'] ?? false));
                    } catch (DomainRuleException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }
                    Notification::make()->success()->title('Reembolso hecho y factura rectificativa emitida')->send();
                    $this->redirect(OrderResource::getUrl('view', ['record' => $order]));
                }),
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
                Action::make('mailShipped')
                    ->label('Correo «Pedido enviado»')
                    ->visible($order->fulfillment === Fulfillment::Delivery)
                    ->url(fn () => route('admin.order-mail.preview', [$order, 'enviado']), shouldOpenInNewTab: true),
                Action::make('mailReady')
                    ->label('Correo «Listo para recoger»')
                    ->visible($order->fulfillment === Fulfillment::Pickup)
                    ->url(fn () => route('admin.order-mail.preview', [$order, 'listo-para-recoger']), shouldOpenInNewTab: true),
            ])->label('Ver correos')->icon('heroicon-o-envelope')->color('gray')->button(),
        ];
    }
}
