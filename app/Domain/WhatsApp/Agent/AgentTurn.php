<?php

namespace App\Domain\WhatsApp\Agent;

/** What the model answered in one call. */
final class AgentTurn
{
    /**
     * @param  string  $stopReason  end_turn · tool_use · refusal · max_tokens · pause_turn…
     * @param  string  $text  Visible text of the answer (all text blocks joined).
     * @param  list<array{id: string, name: string, input: array<string, mixed>}>  $toolCalls
     * @param  array<int, mixed>  $content  The model's content blocks, sent back unchanged inside the tool loop.
     * @param  array<string, int|string|null>  $usage  Token counts, for the message log.
     */
    public function __construct(
        public readonly string $stopReason,
        public readonly string $text,
        public readonly array $toolCalls = [],
        public readonly array $content = [],
        public readonly array $usage = [],
    ) {}
}
