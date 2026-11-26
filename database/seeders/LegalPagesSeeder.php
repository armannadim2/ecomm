<?php

namespace Database\Seeders;

use App\Domain\Content\LegalTexts;
use App\Domain\Content\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the legal pages a Spanish online shop needs (texts in App\Domain\Content\LegalTexts),
 * as DRAFTS: the owner fills the legal name and NIF in "Datos de la tienda", reviews the texts
 * and publishes them.
 *
 * Safe to run again: existing pages are never overwritten (use `php artisan legal:refresh`).
 */
class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $position = 0;

        foreach (LegalTexts::pages() as $slug => $page) {
            Page::query()->withTrashed()->firstOrCreate(['slug' => $slug], [
                'title' => $page['title'],
                'body' => $page['body'],
                'translations' => $page['translations'] ?? null,
                'type' => Page::TYPE_LEGAL,
                'is_published' => false,
                'show_in_footer' => true,
                'position' => $position++,
            ]);
        }
    }
}
