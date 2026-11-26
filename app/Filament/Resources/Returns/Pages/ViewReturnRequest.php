<?php

namespace App\Filament\Resources\Returns\Pages;

use App\Domain\Orders\Actions\ChangeReturnStatus;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Payments\Actions\RefundOrder;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Filament\Resources\Returns\ReturnRequestResource;
use App\Filament\Support\ConfirmPassword;
use App\Filament\Support\MoneyInput;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/** Request page: one button per next status (approve, reject, mark received, mark refunded/exchanged) and a staff note. */
class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    public function getTitle(): string
    {
        return 'Devolución '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        /** @var ReturnRequest $returnRequest */
        $returnRequest = $this->getRecord();

        $statusActions = collect($returnRequest->status->next($returnRequest->type))
            ->map(function (ReturnStatus $to) use ($returnRequest) {
                $rejecting = $to === ReturnStatus::Rejected;

                return Action::make('to_'.$to->value)
                    ->label(match ($to) {
                        ReturnStatus::Approved => 'Aprobar',
                        ReturnStatus::Rejected => 'Rechazar',
                        ReturnStatus::Received => 'Marcar como recibida',
                        ReturnStatus::Refunded => 'Marcar como reembolsada',
                        ReturnStatus::Exchanged => 'Marcar cambio entregado',
                        default => $to->getLabel(),
                    })
                    ->color($rejecting ? 'danger' : ($to === ReturnStatus::Refunded || $to === ReturnStatus::Exchanged ? 'success' : 'primary'))
                    ->requiresConfirmation(! $rejecting)
                    ->modalDescription($rejecting
                        ? 'El cliente recibirá este motivo por correo.'
                        : ($to === ReturnStatus::Refunded
                            ? ($returnRequest->order->payment_method->isOnline()
                                ? 'El dinero vuelve a la tarjeta, Bizum o PayPal del cliente, se emite la factura rectificativa y se le avisa por correo.'
                                : 'Devuelve el dinero en el mostrador y confírmalo aquí: se emite la factura rectificativa y se le avisa por correo.')
                            : 'Se lo avisamos al cliente por correo.'))
                    ->schema(match (true) {
                        $rejecting => [Textarea::make('rejection_reason')->label('Motivo del rechazo')->required()->rows(3)->maxLength(500)],
                        $to === ReturnStatus::Refunded && $returnRequest->order->refundableCents() > 0 => [
                            MoneyInput::make('amount_cents')->label('Importe a devolver')->required()
                                ->default(min($returnRequest->refund_cents ?: $returnRequest->order->refundableCents(), $returnRequest->order->refundableCents()))
                                ->helperText('Máximo '.Money::format($returnRequest->order->refundableCents()).'.'),
                            Toggle::make('manual')->label('Ya lo he devuelto yo (efectivo o transferencia)')
                                ->visible($returnRequest->order->payment_method->isOnline()),
                            ConfirmPassword::field(),
                        ],
                        default => [],
                    })
                    ->visible(fn () => auth()->user()->can('update', $returnRequest)
                        && ($to !== ReturnStatus::Refunded || auth()->user()->can('orders.refund')))
                    ->action(function (array $data, ChangeReturnStatus $change, RefundOrder $refund) use ($returnRequest, $to) {
                        try {
                            if ($to === ReturnStatus::Refunded && isset($data['amount_cents'])) {
                                $refund->handle($returnRequest->order, (int) $data['amount_cents'], 'Devolución '.$returnRequest->number,
                                    auth()->user(), (bool) ($data['manual'] ?? false), $returnRequest);
                            }
                            $change->handle($returnRequest, $to, $data['rejection_reason'] ?? null);
                        } catch (DomainRuleException $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();

                            return;
                        }
                        Notification::make()->success()->title('Solicitud: '.$to->getLabel())->send();
                        $this->redirect(ReturnRequestResource::getUrl('view', ['record' => $returnRequest]));
                    });
            })
            ->values()
            ->all();

        return [
            ...$statusActions,
            Action::make('note')
                ->label('Nota interna')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->visible(fn () => auth()->user()->can('update', $returnRequest))
                ->fillForm(fn () => ['staff_note' => $returnRequest->staff_note])
                ->schema([Textarea::make('staff_note')->label('Nota (solo la ve el equipo)')->rows(4)->maxLength(2000)])
                ->action(fn (array $data) => $returnRequest->update(['staff_note' => $data['staff_note']])),
        ];
    }
}
