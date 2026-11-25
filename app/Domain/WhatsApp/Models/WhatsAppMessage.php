<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One message in a WhatsApp conversation (from the customer, the assistant, staff, or a system note). */
#[Fillable(['conversation_id', 'direction', 'author', 'body', 'wa_message_id', 'status', 'error', 'staff_id', 'meta'])]
class WhatsAppMessage extends Model
{
    public const IN = 'in';

    public const OUT = 'out';

    public const AUTHOR_CUSTOMER = 'customer';

    public const AUTHOR_AGENT = 'agent';

    public const AUTHOR_STAFF = 'staff';

    public const AUTHOR_SYSTEM = 'system';

    protected $table = 'whatsapp_messages';

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
