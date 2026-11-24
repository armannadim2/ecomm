<?php

namespace App\Filament\Widgets;

use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;
use App\Filament\Pages\ManageStoreSettings;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use Filament\Widgets\Widget;

/**
 * "Pon tu tienda en marcha": a checklist on the dashboard that disappears
 * once every step is done. Payment and shipping steps join in later milestones.
 */
class LaunchChecklist extends Widget
{
    protected string $view = 'filament.widgets.launch-checklist';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return ($user?->can('settings.manage') ?? false) && ! self::allDone();
    }

    /** @return list<array{label: string, help: string, done: bool, url: ?string}> */
    public static function steps(): array
    {
        $settings = app(StoreSettings::class);
        $legalSlugs = ['aviso-legal', 'politica-de-privacidad', 'politica-de-cookies', 'condiciones-generales'];

        return [
            [
                'label' => 'Completa los datos de la tienda',
                'help' => 'Razón social, NIF, dirección y correo. La ley obliga a mostrarlos.',
                'done' => $settings->hasLegalData(),
                'url' => ManageStoreSettings::getUrl(),
            ],
            [
                'label' => 'Revisa y publica los textos legales',
                'help' => 'Aviso legal, privacidad, cookies y condiciones de compra.',
                'done' => Page::query()->published()->whereIn('slug', $legalSlugs)->count() === count($legalSlugs),
                'url' => PageResource::getUrl(),
            ],
            [
                'label' => 'Publica tu primer producto',
                'help' => 'Con foto, precio y categoría.',
                'done' => Product::query()->published()->exists(),
                'url' => ProductResource::getUrl(),
            ],
        ];
    }

    public static function allDone(): bool
    {
        return collect(self::steps())->every(fn (array $step) => $step['done']);
    }

    protected function getViewData(): array
    {
        return ['steps' => self::steps()];
    }
}
