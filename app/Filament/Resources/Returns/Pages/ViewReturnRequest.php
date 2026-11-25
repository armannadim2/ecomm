<?php

namespace App\Filament\Resources\Returns\Pages;

use App\Domain\Orders\Actions\ChangeReturnStatus;
use App\Domain\Orders\Enums\ReturnStatus;
use App\Domain\Orders\Models\ReturnRequest;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Filament\Resources\Returns\ReturnRequestResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
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
                            ? 'Confirma que ya has devuelto el dinero al cliente (efectivo en la tienda o el medio de pago original). Desde aquí no se mueve dinero. Se lo avisamos por correo.'
                            : 'Se lo avisamos al cliente por correo.'))
                    ->schema($rejecting ? [Textarea::make('rejection_reason')->label('Motivo del rechazo')->required()->rows(3)->maxLength(500)] : [])
                    ->visible(fn () => auth()->user()->can('update', $returnRequest))
                    ->action(function (array $data, ChangeReturnStatus $change) use ($returnRequest, $to) {
                        try {
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
