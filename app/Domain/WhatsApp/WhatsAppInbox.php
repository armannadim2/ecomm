<?php

namespace App\Domain\WhatsApp;

use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Staff\Models\Staff;
use App\Domain\WhatsApp\Jobs\AnswerWhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppMessage;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Filament\Resources\WhatsAppConversations\WhatsAppConversationResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Everything that changes a WhatsApp conversation: incoming messages (from the webhook),
 * replies from the assistant or staff, hand-offs and take-overs.
 */
class WhatsAppInbox
{
    public function __construct(
        private readonly WhatsAppClient $client,
        private readonly WhatsAppSettings $settings,
    ) {}

    /**
     * Stores one incoming message (Meta may deliver the same one twice: the message id makes it
     * harmless) and asks the assistant to answer, or tells staff when a person is handling it.
     *
     * @param  array<string, mixed>  $message  One entry of the webhook's `messages` array.
     */
    public function receive(array $message, ?string $profileName): ?WhatsAppMessage
    {
        $waId = preg_replace('/\D/', '', (string) ($message['from'] ?? ''));
        $id = (string) ($message['id'] ?? '');
        if ($waId === '' || $id === '' || WhatsAppMessage::query()->where('wa_message_id', $id)->exists()) {
            return null;
        }

        $conversation = WhatsAppConversation::query()->firstOrCreate(['wa_id' => $waId]);
        $conversation->fill([
            'profile_name' => $profileName ?: $conversation->profile_name,
            'status' => $conversation->status === WhatsAppConversation::STATUS_CLOSED ? WhatsAppConversation::STATUS_OPEN : $conversation->status,
            'last_inbound_at' => now(),
            'last_message_at' => now(),
        ])->save();

        $stored = $conversation->messages()->create([
            'direction' => WhatsAppMessage::IN,
            'author' => WhatsAppMessage::AUTHOR_CUSTOMER,
            'body' => self::bodyOf($message),
            'wa_message_id' => $id,
            'meta' => ['type' => $message['type'] ?? null],
        ]);

        if ($conversation->agent_paused || ! $this->settings->agentReady()) {
            $this->notifyStaff($conversation, 'Nuevo mensaje de WhatsApp de '.($conversation->profile_name ?: $conversation->displayPhone()), Str::limit($stored->body, 120));
        } else {
            AnswerWhatsAppConversation::dispatch($conversation->id);
        }

        return $stored;
    }

    /** Delivery receipts from Meta (sent → delivered → read, or failed). */
    public function updateStatus(string $waMessageId, string $status, ?string $error = null): void
    {
        WhatsAppMessage::query()->where('wa_message_id', $waMessageId)->update(array_filter([
            'status' => Str::limit($status, 16, ''),
            'error' => $error,
        ], fn ($v) => $v !== null));
    }

    /** Sends a text as the assistant, staff or a system notice and logs it. */
    public function send(WhatsAppConversation $conversation, string $body, string $author, ?Staff $staff = null, array $meta = []): WhatsAppMessage
    {
        if (! $conversation->canReply()) {
            throw new DomainRuleException('Han pasado más de 24 horas desde el último mensaje del cliente: WhatsApp no permite escribirle libremente hasta que vuelva a escribir.');
        }

        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::OUT,
            'author' => $author,
            'body' => $body,
            'staff_id' => $staff?->id,
            'meta' => $meta ?: null,
        ]);

        try {
            $message->update(['wa_message_id' => $this->client->sendText($conversation->wa_id, $body), 'status' => 'sent']);
        } catch (DomainRuleException $e) {
            $message->update(['status' => 'failed', 'error' => $e->getMessage()]);

            throw $e;
        }

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /** The assistant stops; a person takes over (by the assistant's choice or a staff click). */
    public function handOff(WhatsAppConversation $conversation, string $reason, ?Staff $by = null): void
    {
        $conversation->update(['status' => WhatsAppConversation::STATUS_HUMAN, 'agent_paused' => true, 'handoff_reason' => Str::limit($reason, 480)]);

        $conversation->messages()->create([
            'direction' => WhatsAppMessage::OUT,
            'author' => WhatsAppMessage::AUTHOR_SYSTEM,
            'body' => $by ? "{$by->name} atiende la conversación." : 'El asistente pasa la conversación al equipo: '.$reason,
        ]);

        if ($by === null) {
            $this->notifyStaff($conversation, 'WhatsApp: '.($conversation->profile_name ?: $conversation->displayPhone()).' necesita atención', $reason);
        }
    }

    public function resumeAgent(WhatsAppConversation $conversation, Staff $by): void
    {
        $conversation->update(['status' => WhatsAppConversation::STATUS_OPEN, 'agent_paused' => false, 'handoff_reason' => null]);
        $conversation->messages()->create([
            'direction' => WhatsAppMessage::OUT,
            'author' => WhatsAppMessage::AUTHOR_SYSTEM,
            'body' => "{$by->name} devuelve la conversación al asistente.",
        ]);
    }

    public function close(WhatsAppConversation $conversation): void
    {
        $conversation->update(['status' => WhatsAppConversation::STATUS_CLOSED, 'agent_paused' => false, 'handoff_reason' => null]);
    }

    private function notifyStaff(WhatsAppConversation $conversation, string $title, string $body): void
    {
        $recipients = Staff::query()->where('is_active', true)->get()
            ->filter(fn (Staff $staff) => $staff->can('whatsapp.reply'));

        foreach ($recipients as $staff) {
            Notification::make()->info()->title($title)->body($body)
                ->actions([Action::make('open')->label('Abrir')
                    ->url(WhatsAppConversationResource::getUrl('view', ['record' => $conversation]))])
                ->sendToDatabase($staff);
        }
    }

    /** Text of an incoming message; other types become a short note the assistant and staff can read. */
    private static function bodyOf(array $message): string
    {
        $type = (string) ($message['type'] ?? 'unknown');

        $text = match ($type) {
            'text' => $message['text']['body'] ?? '',
            'button' => $message['button']['text'] ?? '',
            'interactive' => $message['interactive']['button_reply']['title'] ?? $message['interactive']['list_reply']['title'] ?? '',
            default => '',
        };

        if (trim((string) $text) !== '') {
            return Str::limit((string) $text, 4000);
        }

        $label = ['image' => 'una imagen', 'audio' => 'un audio', 'video' => 'un vídeo', 'document' => 'un documento', 'sticker' => 'un sticker', 'location' => 'una ubicación', 'contacts' => 'un contacto'][$type] ?? 'un mensaje que no es texto';

        return "[El cliente ha enviado {$label}. El asistente solo lee texto.]";
    }
}
