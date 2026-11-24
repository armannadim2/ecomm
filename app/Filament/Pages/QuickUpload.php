<?php

namespace App\Filament\Pages;

use App\Domain\Catalog\Actions\QuickCreateProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Exceptions\DomainRuleException;
use App\Domain\Store\Settings\StoreSettings;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\MoneyInput;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * "Subir producto": the fastest way to add a product, designed for phones.
 * Photos first, then title, price and category. Like Vinted / Wallapop.
 *
 * Layout follows the Stitch design "Afegir Producte - Tot & Més" (numbered cards on the left,
 * live preview of the shop card on the right): view filament/pages/quick-upload.blade.php.
 *
 * @property-read Schema $form
 */
class QuickUpload extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Subir producto';

    protected static ?string $title = 'Subir producto';

    protected static ?string $slug = 'subir-producto';

    protected string $view = 'filament.pages.quick-upload';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', Product::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['stock' => 1]);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Añadir nuevo producto a la tienda';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Sube el artículo al catálogo online en menos de un minuto. Empieza por las fotos.';
    }

    /** @return array<string, string> */
    public function getBreadcrumbs(): array
    {
        return [
            ProductResource::getUrl() => 'Catálogo de productos',
            'Nuevo artículo',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancelar y volver')
                ->color('gray')
                ->url(ProductResource::getUrl()),
            Action::make('publishTop')
                ->label($this->submitLabel())
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->submit('save')
                ->formId('quick-upload-form'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(self::stepHeading(1, 'Imágenes del producto'))
                    ->description('Fotos reales con buena luz y fondo neutro. La primera será la principal.')
                    ->afterHeader([new HtmlString('<span class="qu-badge">Mínimo 1 foto</span>')])
                    ->schema([
                        FileUpload::make('photos')
                            ->hiddenLabel()
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->minFiles(1)
                            ->maxFiles(10)
                            ->maxSize(8192)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('local')
                            ->directory('quick-uploads')
                            ->visibility('private')
                            ->panelLayout('grid')
                            ->placeholder('Arrastra las fotos aquí o pulsa para subirlas desde el móvil u ordenador · JPG, PNG o WebP, hasta 8 MB y 10 fotos')
                            ->required()
                            ->validationMessages(['required' => 'Añade al menos una foto.']),
                    ]),
                Section::make(self::stepHeading(2, 'Información general y clasificación'))
                    ->description('Un nombre claro ayuda a que los clientes lo encuentren rápido.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre comercial del producto')
                            ->placeholder('Ej.: Cafetera italiana de acero inoxidable 6 tazas')
                            ->hint(new HtmlString('<span x-text="($wire.data?.name ?? \'\').length + \' / 120 caracteres\'">0 / 120 caracteres</span>'))
                            ->required()
                            ->maxLength(120),
                        Select::make('category_id')
                            ->label('Categoría')
                            ->options(fn () => $this->categoryOptions())
                            ->searchable()
                            ->required(),
                        Textarea::make('short_description')
                            ->label('Descripción del producto')
                            ->placeholder('Estado, medidas, material… lo que preguntaría un cliente.')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Section::make(self::stepHeading(3, 'Precio'))
                            ->description('Precio de venta al público.')
                            ->schema([
                                MoneyInput::make('price_cents')
                                    ->label('PVP (IVA incluido)')
                                    ->helperText('Precio final que paga el cliente.')
                                    ->required(),
                            ]),
                        Section::make(self::stepHeading(4, 'Inventario'))
                            ->description('Unidades listas para vender.')
                            ->schema([
                                TextInput::make('stock')
                                    ->label('Unidades disponibles')
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('quick-upload-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label(fn () => $this->submitLabel())
                            ->icon(Heroicon::OutlinedCloudArrowUp)
                            ->size('lg')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->alignment(Alignment::End),
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'categoryNames' => $this->categoryOptions(),
            'storeName' => app(StoreSettings::class)->store_name,
            'canPublish' => auth()->user()->can('products.publish'),
        ];
    }

    public function save(QuickCreateProduct $create): void
    {
        $data = $this->form->getState();

        try {
            $product = $create->handle([
                'name' => $data['name'],
                'price_cents' => (int) $data['price_cents'],
                'category_id' => (int) $data['category_id'],
                'stock' => (int) $data['stock'],
                'short_description' => $data['short_description'] ?? null,
                'photos' => array_values($data['photos']),
            ], auth()->user());
        } catch (DomainRuleException $e) {
            Notification::make()->danger()->title('No se ha podido subir')->body($e->getMessage())->send();

            return;
        }

        Notification::make()
            ->success()
            ->title($product->status === ProductStatus::Published ? '¡Producto publicado!' : 'Enviado a revisión')
            ->body($product->status === ProductStatus::Published
                ? 'Ya está visible en la tienda.'
                : 'Te avisaremos cuando esté publicado.')
            ->actions([
                Action::make('open')->label('Ver producto')->url(ProductResource::getUrl('edit', ['record' => $product])),
            ])
            ->send();

        // Ready for the next one.
        $this->form->fill(['stock' => 1]);
    }

    private function submitLabel(): string
    {
        return auth()->user()->can('products.publish') ? 'Publicar en la tienda' : 'Enviar a revisión';
    }

    /** @return array<int, string> */
    private function categoryOptions(): array
    {
        return once(fn () => Category::query()->orderBy('name')->pluck('name', 'id')->all());
    }

    private static function stepHeading(int $step, string $title): HtmlString
    {
        return new HtmlString('<span class="qu-step" aria-hidden="true">'.$step.'</span>'.e($title));
    }
}
