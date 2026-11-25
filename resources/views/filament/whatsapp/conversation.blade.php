{{-- WhatsApp conversation thread (ViewWhatsAppConversation). Customer on the left, shop on the right. --}}
@php use App\Domain\WhatsApp\Models\WhatsAppMessage; @endphp
<x-filament-panels::page>
    <div wire:poll.15s class="mx-auto flex w-full max-w-3xl flex-col gap-3">
        @if ($this->record->handoff_reason && $this->record->agent_paused)
            <div class="rounded-xl bg-warning-50 px-4 py-3 text-sm text-warning-800 ring-1 ring-warning-200 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-500/20">
                <strong>Necesita una persona:</strong> {{ $this->record->handoff_reason }}
            </div>
        @endif

        @forelse ($this->messages() as $message)
            @if ($message->author === WhatsAppMessage::AUTHOR_SYSTEM)
                <p class="text-center text-xs text-gray-500">{{ $message->body }} · {{ $message->created_at->timezone('Europe/Madrid')->format('d/m H:i') }}</p>
            @else
                @php $out = $message->direction === WhatsAppMessage::OUT; @endphp
                <div @class(['flex', 'justify-end' => $out])>
                    <div @class([
                        'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm shadow-sm',
                        'bg-white text-gray-900 ring-1 ring-gray-200 dark:bg-white/5 dark:text-white dark:ring-white/10' => ! $out,
                        'bg-primary-50 text-gray-900 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-white dark:ring-primary-500/20' => $out,
                    ])>
                        <p class="whitespace-pre-line break-words">{{ $message->body }}</p>
                        <p class="mt-1 text-right text-[11px] text-gray-500">
                            @if ($message->author === WhatsAppMessage::AUTHOR_AGENT) Asistente ·
                            @elseif ($message->author === WhatsAppMessage::AUTHOR_STAFF) {{ $message->staff?->name ?? 'Equipo' }} ·
                            @endif
                            {{ $message->created_at->timezone('Europe/Madrid')->format('d/m H:i') }}
                            @if ($message->status === 'failed')
                                · <span class="font-semibold text-danger-600" title="{{ $message->error }}">No enviado</span>
                            @elseif ($out && $message->status)
                                · {{ ['sent' => 'enviado', 'delivered' => 'entregado', 'read' => 'leído'][$message->status] ?? $message->status }}
                            @endif
                        </p>
                    </div>
                </div>
            @endif
        @empty
            <p class="text-center text-sm text-gray-500">Todavía no hay mensajes.</p>
        @endforelse
    </div>
</x-filament-panels::page>
