<?php

namespace App\Domain\WhatsApp\Agent;

/** Outcome of one assistant run: the text to send (if any) and whether a person must take over. */
final class AgentResult
{
    /** @param  list<array<string, mixed>>  $usage  Token usage per model call. */
    public function __construct(
        public readonly ?string $text,
        public readonly ?string $handoffReason = null,
        public readonly bool $refused = false,
        public readonly array $usage = [],
    ) {}

    public function needsHuman(): bool
    {
        return $this->handoffReason !== null;
    }
}
