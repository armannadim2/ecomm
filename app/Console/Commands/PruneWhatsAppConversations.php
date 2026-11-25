<?php

namespace App\Console\Commands;

use App\Domain\WhatsApp\Models\WhatsAppConversation;
use Illuminate\Console\Command;

/**
 * Deletes WhatsApp conversations with no activity for 2 years (privacy policy: customer service
 * conversations are kept up to 2 years). Messages go with them (cascade).
 */
class PruneWhatsAppConversations extends Command
{
    protected $signature = 'whatsapp:prune {--years=2}';

    protected $description = 'Delete WhatsApp conversations older than the retention period';

    public function handle(): int
    {
        $deleted = WhatsAppConversation::query()
            ->where(fn ($q) => $q->where('last_message_at', '<', now()->subYears((int) $this->option('years')))
                ->orWhere(fn ($q) => $q->whereNull('last_message_at')->where('created_at', '<', now()->subYears((int) $this->option('years')))))
            ->delete();

        $this->info("Conversaciones eliminadas: {$deleted}");

        return self::SUCCESS;
    }
}
