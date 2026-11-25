<?php

namespace App\Filament\Resources\WhatsAppConversations\Pages;

use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Filament\Resources\WhatsAppConversations\WhatsAppConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConversations extends ListRecords
{
    protected static string $resource = WhatsAppConversationResource::class;

    public function getSubheading(): ?string
    {
        $settings = app(WhatsAppSettings::class);

        return match (true) {
            ! $settings->isConfigured() => 'WhatsApp todavía no está conectado: completa «Ajustes de WhatsApp».',
            ! $settings->agentReady() => 'El asistente está desactivado: todas las conversaciones esperan a una persona.',
            default => 'El asistente responde al momento y te avisa cuando una conversación necesita a una persona.',
        };
    }
}
