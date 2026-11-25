<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\WhatsApp\Policies\WhatsAppPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A WhatsApp chat with one customer number. While `agent_paused` is true (a person took over,
 * or the assistant handed off), the AI assistant does not answer.
 */
#[Fillable(['wa_id', 'profile_name', 'status', 'agent_paused', 'handoff_reason', 'last_inbound_at', 'last_message_at'])]
#[UsePolicy(WhatsAppPolicy::class)]
class WhatsAppConversation extends Model
{
    use HasUlids;

    public const STATUS_OPEN = 'open';

    public const STATUS_HUMAN = 'human';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'whatsapp_conversations';

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'agent_paused' => false,
    ];

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    protected function casts(): array
    {
        return [
            'agent_paused' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'conversation_id')->latestOfMany();
    }

    /** Meta lets businesses send free-form messages only within 24 h of the customer's last message. */
    public function canReply(): bool
    {
        return $this->last_inbound_at !== null && $this->last_inbound_at->gt(now()->subHours(24));
    }

    public function displayPhone(): string
    {
        return '+'.$this->wa_id;
    }
}
