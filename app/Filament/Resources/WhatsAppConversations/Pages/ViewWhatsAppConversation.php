<?php

namespace App\Filament\Resources\WhatsAppConversations\Pages;

use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppMessage;
use App\Domain\WhatsApp\WhatsAppInbox;
use App\Filament\Resources\WhatsAppConversations\WhatsAppConversationResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * One WhatsApp conversation: the thread plus "Responder", "Atender yo", "Devolver al asistente"
 * and "Cerrar". Replying as staff pauses the assistant automatically.
 *
 * @property WhatsAppConversation $record
 */
class ViewWhatsAppConversation extends ViewRecord
{
    protected static string $resource = WhatsAppConversationResource::class;

    protected string $view = 'filament.whatsapp.conversation';

    public function getTitle(): string
    {
        return ($this->record->profile_name ?: 'Cliente').' · '.$this->record->displayPhone();
    }

    /** @return Collection<int, WhatsAppMessage> */
    public function messages(): Collection
    {
        return $this->record->messages()->with('staff')->reorder('id', 'desc')->limit(200)->get()->reverse()->values();
    }

    protected function getHeaderActions(): array
    {
        $inbox = app(WhatsAppInbox::class);

        return [
            Action::make('reply')
                ->label('Responder')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->authorize('reply')
                ->disabled(fn () => ! $this->record->canReply())
                ->tooltip(fn () => $this->record->canReply() ? null : 'Han pasado más de 24 h desde el último mensaje del cliente.')
                ->schema([Textarea::make('body')->label('Mensaje')->required()->rows(4)->maxLength(4000)])
                ->modalDescription('Al responder tú, el asistente deja de contestar en esta conversación.')
                ->action(function (array $data) use ($inbox) {
                    try {
                        if (! $this->record->agent_paused) {
                            $inbox->handOff($this->record, 'Respuesta del equipo', auth()->user());
                        }
                        $inbox->send($this->record, $data['body'], WhatsAppMessage::AUTHOR_STAFF, auth()->user());
                        Notification::make()->success()->title('Mensaje enviado')->send();
                    } catch (DomainRuleException $e) {
                        Notification::make()->danger()->title('No se ha enviado')->body($e->getMessage())->send();
                    }
                }),
            Action::make('takeOver')
                ->label('Atender yo')
                ->icon(Heroicon::OutlinedUser)
                ->color('warning')
                ->authorize('reply')
                ->visible(fn () => ! $this->record->agent_paused)
                ->action(function () use ($inbox) {
                    $inbox->handOff($this->record, 'Atiende una persona', auth()->user());
                    Notification::make()->success()->title('El asistente deja de responder en esta conversación')->send();
                }),
            Action::make('resume')
                ->label('Devolver al asistente')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->authorize('reply')
                ->visible(fn () => $this->record->agent_paused)
                ->action(function () use ($inbox) {
                    $inbox->resumeAgent($this->record, auth()->user());
                    Notification::make()->success()->title('El asistente vuelve a responder')->send();
                }),
            Action::make('close')
                ->label('Cerrar')
                ->icon(Heroicon::OutlinedCheck)
                ->color('gray')
                ->authorize('reply')
                ->visible(fn () => $this->record->status !== WhatsAppConversation::STATUS_CLOSED)
                ->action(function () use ($inbox) {
                    $inbox->close($this->record);
                    Notification::make()->success()->title('Conversación cerrada')->send();
                }),
        ];
    }
}
