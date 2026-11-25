<?php

namespace App\Console\Commands;

use App\Domain\Content\LegalTexts;
use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;
use Illuminate\Console\Command;

/**
 * Rewrites the five legal pages with the current texts of App\Domain\Content\LegalTexts
 * (staff edits to those pages are replaced; the change is in the activity log).
 * --publish also publishes them and shows them in the footer.
 */
class RefreshLegalPages extends Command
{
    protected $signature = 'legal:refresh {--publish : Publish the pages too} {--force : Do not ask for confirmation}';

    protected $description = 'Replace the legal pages with the current legal texts';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Se sustituirán los textos de las páginas legales. ¿Continuar?', true)) {
            return self::FAILURE;
        }

        $position = 0;

        foreach (LegalTexts::pages() as $slug => $text) {
            $page = Page::query()->withTrashed()->firstOrNew(['slug' => $slug]);
            $page->fill([
                'title' => $text['title'],
                'body' => $text['body'],
                'type' => Page::TYPE_LEGAL,
                'show_in_footer' => true,
                'position' => $page->exists ? $page->position : $position,
            ]);

            if ($this->option('publish')) {
                $page->is_published = true;
            }

            $page->deleted_at = null;
            $page->save();
            $position++;

            $this->line(($page->is_published ? '✔ publicada  ' : '· borrador   ').$page->title);
        }

        $store = app(StoreSettings::class);
        if (blank($store->legal_name) || blank($store->nif)) {
            $this->warn('Falta el nombre legal o el NIF en «Datos de la tienda»: en las páginas aparecerá «—» hasta que los rellenes.');
        }

        return self::SUCCESS;
    }
}
