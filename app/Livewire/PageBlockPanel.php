<?php

namespace App\Livewire;

use App\Filament\Blocks\PageBlocks;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Side panel of the visual editor: the form of the selected block (same fields as the classic
 * editor, with Español / Català / English tabs). Each change is sent to the editor, which
 * updates its working copy and refreshes the preview.
 *
 * @property-read Schema $form
 */
class PageBlockPanel extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public string $type;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @param  array<string, mixed>  $blockData */
    public function mount(string $type, array $blockData): void
    {
        $this->type = $type;
        $this->form->fill($blockData);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(PageBlocks::schemaFor($this->type))
            ->statePath('data');
    }

    /** Validates the panel and hands the block data to the editor. */
    public function apply(): void
    {
        $this->dispatch('pb-block-data', data: $this->form->getState());
    }

    public function render(): View
    {
        return view('livewire.page-block-panel');
    }
}
