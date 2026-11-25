<?php

namespace App\Domain\WhatsApp\Agent;

use Anthropic\Beta\Messages\BetaMessage;
use Anthropic\Client;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;

/**
 * Claude through the official Anthropic PHP SDK (Messages API, beta namespace for fallbacks).
 *
 * - Model from the WhatsApp settings (default claude-opus-5-5). Effort "low": short chat answers.
 *   Thinking is left out, so it runs adaptive (it cannot be switched off on Claude Opus 5.5).
 * - Server-side refusal fallbacks ("default") on the models that support them: if the model
 *   declines for policy reasons, the API retries on a fallback model.
 * - The system prompt is frozen and marked for prompt caching (tools are cached with it).
 */
class ClaudeAgentModel implements AgentModel
{
    /** Models that accept `fallbacks: "default"`. */
    private const FALLBACK_MODELS = ['claude-opus-5-5', 'claude-opus-5', 'claude-fable-5-2', 'claude-fable-5-1', 'claude-sonnet-5-5'];

    private ?Client $client = null;

    public function __construct(private readonly WhatsAppSettings $settings) {}

    public function turn(string $system, array $messages, array $tools): AgentTurn
    {
        $model = $this->settings->model ?: 'claude-opus-5-5';
        $fallbacks = in_array($model, self::FALLBACK_MODELS, true);

        $response = $this->client()->beta->messages->create(
            maxTokens: 4000,
            messages: $messages,
            model: $model,
            fallbacks: $fallbacks ? 'default' : null,
            outputConfig: ['effort' => 'low'],
            system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
            tools: $tools,
            betas: $fallbacks ? ['server-side-fallback-2026-07-01'] : null,
        );

        return $this->toTurn($response);
    }

    private function toTurn(BetaMessage $response): AgentTurn
    {
        $text = [];
        $calls = [];

        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text[] = $block->text;
            } elseif ($block->type === 'tool_use') {
                $calls[] = ['id' => $block->id, 'name' => $block->name, 'input' => (array) $block->input];
            }
        }

        return new AgentTurn(
            stopReason: (string) $response->stopReason,
            text: trim(implode("\n\n", $text)),
            toolCalls: $calls,
            content: $response->content,
            usage: [
                'model' => $response->model,
                'input_tokens' => $response->usage->inputTokens,
                'output_tokens' => $response->usage->outputTokens,
                'cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
            ],
        );
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: (string) $this->settings->anthropic_api_key);
    }
}
