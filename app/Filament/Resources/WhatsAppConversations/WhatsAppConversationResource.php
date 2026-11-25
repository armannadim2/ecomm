<?php

namespace App\Filament\Resources\WhatsAppConversations;

use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Filament\Resources\WhatsAppConversations\Pages\ListWhatsAppConversations;
use App\Filament\Resources\WhatsAppConversations\Pages\ViewWhatsAppConversation;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/** Atención al cliente → WhatsApp: conversations with customers, answered by the assistant or staff. */
class WhatsAppConversationResource extends Resource
{
    protected static ?string $model = WhatsAppConversation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al cliente';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'WhatsApp';

    protected static ?string $modelLabel = 'conversación';

    protected static ?string $pluralModelLabel = 'conversaciones de WhatsApp';

    protected static ?string $slug = 'whatsapp';

    public const STATUS_LABELS = [
        WhatsAppConversation::STATUS_OPEN => 'Asistente',
        WhatsAppConversation::STATUS_HUMAN => 'Necesita una persona',
        WhatsAppConversation::STATUS_CLOSED => 'Cerrada',
    ];

    /** Sidebar badge: conversations waiting for a person. */
    public static function getNavigationBadge(): ?string
    {
        $count = WhatsAppConversation::query()->where('status', WhatsAppConversation::STATUS_HUMAN)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('latestMessage'))
            ->defaultSort('last_message_at', 'desc')
            ->poll('20s')
            ->columns([
                TextColumn::make('profile_name')->label('Cliente')->searchable()->placeholder('Sin nombre')
                    ->description(fn (WhatsAppConversation $r) => $r->displayPhone()),
                TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        WhatsAppConversation::STATUS_HUMAN => 'warning',
                        WhatsAppConversation::STATUS_CLOSED => 'gray',
                        default => 'success',
                    }),
                TextColumn::make('latestMessage.body')->label('Último mensaje')->wrap()
                    ->formatStateUsing(fn (?string $state) => Str::limit((string) $state, 90)),
                IconColumn::make('agent_paused')->label('Atiende una persona')->boolean(),
                TextColumn::make('last_message_at')->label('Actividad')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(self::STATUS_LABELS),
            ])
            ->recordActions([ViewAction::make()->label('Abrir')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhatsAppConversations::route('/'),
            'view' => ViewWhatsAppConversation::route('/{record}'),
        ];
    }
}
