<?php

namespace App\Domain\WhatsApp\Jobs;

use App\Domain\WhatsApp\Agent\CustomerAgent;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppMessage;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Domain\WhatsApp\WhatsAppInbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the assistant for one conversation and sends its answer. Unique per conversation, so a
 * customer who sends three quick messages gets one answer to all of them. Not retried: a retry
 * could send the customer the same answer twice.
 */
class AnswerWhatsAppConversation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 120;

    public function __construct(public int $conversationId) {}

    public function uniqueId(): string
    {
        return (string) $this->conversationId;
    }

    public function handle(CustomerAgent $agent, WhatsAppInbox $inbox, WhatsAppSettings $settings): void
    {
        $conversation = WhatsAppConversation::query()->find($this->conversationId);
        if ($conversation === null || $conversation->agent_paused || ! $settings->agentReady() || ! $conversation->canReply()) {
            return;
        }

        try {
            $result = $agent->reply($conversation);
        } catch (Throwable $e) {
            Log::error('WhatsApp assistant failed', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);
            $this->handOffWithNotice($inbox, $settings, $conversation, 'El asistente no está disponible ahora mismo ('.class_basename($e).').');

            return;
        }

        if ($result === null) {
            return;
        }

        $meta = ['usage' => $result->usage];

        try {
            if ($result->text !== null) {
                $inbox->send($conversation, $result->text, WhatsAppMessage::AUTHOR_AGENT, meta: $meta);
            }

            if ($result->needsHuman()) {
                $inbox->handOff($conversation, (string) $result->handoffReason);
                if ($result->text === null) {
                    $inbox->send($conversation, $settings->handoff_message, WhatsAppMessage::AUTHOR_AGENT, meta: $meta);
                }
            }
        } catch (Throwable $e) {
            Log::error('WhatsApp reply could not be sent', ['conversation' => $conversation->id, 'error' => $e->getMessage()]);
            if (! $conversation->fresh()->agent_paused) {
                $inbox->handOff($conversation, 'No se pudo enviar la respuesta del asistente: '.$e->getMessage());
            }
        }
    }

    private function handOffWithNotice(WhatsAppInbox $inbox, WhatsAppSettings $settings, WhatsAppConversation $conversation, string $reason): void
    {
        $inbox->handOff($conversation, $reason);

        try {
            $inbox->send($conversation, $settings->handoff_message, WhatsAppMessage::AUTHOR_AGENT);
        } catch (Throwable) {
            // Already handed off; staff see the failed message in the inbox.
        }
    }
}
