<?php

use App\Domain\Content\Blocks\DefaultContent;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = DefaultContent::layout();

        foreach (['announcement', 'header', 'value_props', 'footer'] as $key) {
            $this->migrator->add('storefront.'.$key, $defaults[$key]);
        }
    }
};
