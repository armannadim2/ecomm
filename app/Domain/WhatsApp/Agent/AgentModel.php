<?php

namespace App\Domain\WhatsApp\Agent;

/**
 * The language model behind the WhatsApp assistant. ClaudeAgentModel is the real one;
 * tests bind a scripted fake.
 */
interface AgentModel
{
    /**
     * One Messages API call.
     *
     * @param  string  $system  Frozen instructions (cached).
     * @param  list<array<string, mixed>>  $messages  Conversation; assistant turns inside a tool loop carry the model's own content blocks.
     * @param  list<array<string, mixed>>  $tools  Tool definitions (name, description, inputSchema).
     */
    public function turn(string $system, array $messages, array $tools): AgentTurn;
}
