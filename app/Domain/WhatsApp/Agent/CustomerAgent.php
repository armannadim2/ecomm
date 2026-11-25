<?php

namespace App\Domain\WhatsApp\Agent;

use App\Domain\Content\Blocks\Block;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Store\Settings\StoreSettings;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppMessage;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use Illuminate\Support\Facades\Log;

/**
 * The WhatsApp customer-service assistant: turns a conversation into one reply.
 *
 * Messages API with a tool loop we host (shared hosting, tools read the shop database, runs in a
 * short queued job). How the conversation is sent:
 * - The system prompt is frozen (same bytes every time → prompt cache); shop facts come from tools.
 * - Earlier turns are rebuilt as plain text from our own message log. That drops every earlier
 *   thinking block, which the API allows; inside one reply's tool loop the model's own content
 *   blocks are sent back unchanged.
 * - Per-reply context (time, customer number, first contact) is appended as a mid-conversation
 *   system message after the newest customer message, so it never changes the cached prefix.
 */
class CustomerAgent
{
    private const HISTORY = 30;

    private const MAX_TOOL_ROUNDS = 5;

    public function __construct(
        private readonly AgentModel $model,
        private readonly WhatsAppSettings $settings,
    ) {}

    /** Null when there is nothing new from the customer to answer. */
    public function reply(WhatsAppConversation $conversation): ?AgentResult
    {
        $messages = $this->history($conversation);
        if ($messages === [] || end($messages)['role'] !== 'user') {
            return null;
        }

        $messages[] = ['role' => 'system', 'content' => $this->context($conversation)];

        $tools = new ShopTools($conversation);
        $usage = [];

        for ($round = 0; $round <= self::MAX_TOOL_ROUNDS; $round++) {
            $turn = $this->model->turn($this->systemPrompt(), $messages, ShopTools::definitions());
            $usage[] = $turn->usage;

            // A declined request has no usable content: never show it, pass to a person.
            if ($turn->stopReason === 'refusal') {
                return new AgentResult(null, handoffReason: 'El asistente no ha podido responder a esta consulta.', refused: true, usage: $usage);
            }

            if ($turn->stopReason !== 'tool_use' || $turn->toolCalls === []) {
                $text = $turn->text !== '' ? $turn->text : null;

                return new AgentResult($text, handoffReason: $tools->handoffReason ?? ($text === null ? 'El asistente no ha generado respuesta.' : null), usage: $usage);
            }

            $results = [];
            foreach ($turn->toolCalls as $call) {
                try {
                    $output = $tools->run($call['name'], $call['input']);
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $call['id'], 'content' => json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
                } catch (\Throwable $e) {
                    Log::warning('WhatsApp assistant tool failed', ['tool' => $call['name'], 'error' => $e->getMessage()]);
                    $results[] = ['type' => 'tool_result', 'toolUseID' => $call['id'], 'content' => 'Error interno al consultar los datos. Discúlpate y ofrece pasar con una persona.', 'isError' => true];
                }
            }

            // All results of one turn go back together in a single user message.
            $messages[] = ['role' => 'assistant', 'content' => $turn->content];
            $messages[] = ['role' => 'user', 'content' => $results];
        }

        return new AgentResult(null, handoffReason: 'Consulta demasiado larga para el asistente.', usage: $usage);
    }

    public function systemPrompt(): string
    {
        $store = app(StoreSettings::class)->store_name;
        $extra = trim((string) $this->settings->instructions);

        return <<<PROMPT
Eres el asistente virtual de WhatsApp de {$store}, un gran bazar de barrio en Barcelona (hogar, cocina, papelería, bricolaje, regalos…) con tienda física y tienda online.

Cómo respondes:
- Responde en el idioma del cliente (normalmente español o catalán; también inglés u otros).
- Mensajes cortos y amables, como en WhatsApp: frases breves, sin títulos ni tablas, emojis con moderación. Usa *negrita* de WhatsApp solo para datos clave.
- En el primer mensaje de una conversación preséntate como asistente virtual de {$store} y di que puede pedir hablar con una persona.

Información:
- Usa siempre las herramientas para datos de la tienda, productos, precios, disponibilidad y pedidos. No inventes nada: si una herramienta no da el dato, dilo y ofrece pasar con una persona.
- Comparte los enlaces que devuelven las herramientas tal cual.
- Por WhatsApp no se hacen pedidos, reservas ni pagos: envía el enlace del producto para comprarlo online con recogida en tienda, o invita a venir a la tienda.
- La disponibilidad es la de la tienda online en este momento; no garantices stock futuro.

Pedidos y privacidad:
- Solo das información de un pedido a través de order_status, con su número y el correo o teléfono del pedido. Si no coinciden, no des ningún dato.
- Nunca pidas ni aceptes datos de tarjeta, contraseñas, códigos de verificación ni fotos de documentos de identidad.
- No compartas el código de recogida por WhatsApp: está en el correo de confirmación.

Pasa a una persona (herramienta handoff_to_human) cuando el cliente lo pida, para quejas, devoluciones, reembolsos, productos defectuosos o dañados, problemas con un pedido que no se resuelven con su estado, solicitudes sobre datos personales (RGPD), pedidos para empresas o grandes cantidades, o si no estás seguro. Después despídete en una frase: una persona del equipo le responderá en horario de tienda.

No hables de temas ajenos a la tienda; redirige amablemente.
PROMPT.($extra !== '' ? "\n\nIndicaciones de la tienda:\n".$extra : '');
    }

    /** @return list<array{role: string, content: string}> */
    private function history(WhatsAppConversation $conversation): array
    {
        $rows = $conversation->messages()
            ->where('author', '!=', WhatsAppMessage::AUTHOR_SYSTEM)
            ->reorder('id', 'desc')->limit(self::HISTORY)->get()->reverse();

        $messages = [];
        foreach ($rows as $row) {
            $role = $row->author === WhatsAppMessage::AUTHOR_CUSTOMER ? 'user' : 'assistant';
            $text = $row->author === WhatsAppMessage::AUTHOR_STAFF ? '(Respuesta de una persona del equipo) '.$row->body : $row->body;

            // Several messages in a row from the same side become one turn.
            if ($messages !== [] && $messages[array_key_last($messages)]['role'] === $role) {
                $messages[array_key_last($messages)]['content'] .= "\n".$text;
            } else {
                $messages[] = ['role' => $role, 'content' => $text];
            }
        }

        while ($messages !== [] && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }

        return $messages;
    }

    private function context(WhatsAppConversation $conversation): string
    {
        $now = now()->timezone('Europe/Madrid');
        $first = $conversation->messages()->where('author', WhatsAppMessage::AUTHOR_AGENT)->doesntExist();
        $hours = (new Block('h', ['h' => app(CheckoutSettings::class)->opening_hours]))->text('h');

        return implode("\n", array_filter([
            'Contexto de este mensaje (no lo cites literalmente):',
            '- Fecha y hora en Barcelona: '.$now->translatedFormat('l j \d\e F \d\e Y, H:i').'.',
            '- Horario de la tienda: '.$hours.'.',
            '- Cliente: '.($conversation->profile_name ?: 'sin nombre de perfil').', escribe desde '.$conversation->displayPhone().'.',
            $first ? '- Es la primera respuesta del asistente en esta conversación: preséntate.' : null,
        ]));
    }
}
