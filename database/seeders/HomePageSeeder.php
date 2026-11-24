<?php

namespace Database\Seeders;

use App\Domain\Content\Blocks\DefaultContent;
use App\Domain\Content\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the editable home page ("inicio") with the Stitch "Tot & Més" blocks in es/ca/en.
 * Safe to run again: an existing home page (and the staff's edits) is never overwritten.
 */
class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->withTrashed()->firstOrCreate(['slug' => Page::HOME_SLUG], [
            'title' => 'Inicio',
            'type' => Page::TYPE_INFO,
            'is_published' => true,
            'show_in_footer' => false,
            'position' => 0,
            'blocks' => DefaultContent::home(),
        ]);
    }
}
