<?php

namespace App\Domain\Content\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Editable texts of the storefront header and footer (announcement bar, value cards, footer columns...).
 * Translatable texts are stored as ['es' => ..., 'ca' => ..., 'en' => ...].
 * Edited by staff in "Cabecera y pie"; defaults in App\Domain\Content\Blocks\DefaultContent::layout().
 */
class StorefrontContentSettings extends Settings
{
    /** Keys: text, extra, code, pill */
    public array $announcement;

    /** Keys: store_pill, store_pill_url, search_placeholder, tagline */
    public array $header;

    /** List of items with: icon, title, text */
    public array $value_props;

    /** Keys: about, chip_location, chip_web, columns, copyright, payments_note, carriers_note */
    public array $footer;

    public static function group(): string
    {
        return 'storefront';
    }
}
