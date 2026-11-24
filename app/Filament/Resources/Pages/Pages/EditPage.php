<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Domain\Content\Blocks\DefaultContent;
use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('Ver en la tienda')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->getRecord()->isHome()
                    ? route('store.home')
                    : route('store.page', $this->getRecord()->slug), shouldOpenInNewTab: true)
                ->visible(fn () => $this->getRecord()->is_published || $this->getRecord()->isHome()),
            Action::make('restoreDesign')
                ->label('Restaurar diseño original')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => $this->getRecord()->isHome())
                ->requiresConfirmation()
                ->modalDescription('Los bloques de la portada volverán a ser los del diseño de Stitch, con sus textos en español, catalán e inglés. Se perderán tus cambios en los bloques.')
                ->action(function () {
                    $this->getRecord()->update(['blocks' => DefaultContent::home()]);
                    $this->fillForm();
                    Notification::make()->success()->title('Portada restaurada')->send();
                }),
            DeleteAction::make()->label('Enviar a la papelera')->hidden(fn () => $this->getRecord()->isHome()),
            RestoreAction::make(),
        ];
    }
}
