<?php

namespace App\Filament\Blocks;

use App\Domain\Catalog\Models\Category;
use App\Domain\Content\Blocks\BlockTypes;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Page-builder forms (Filament Builder). One Builder block per App\Domain\Content\Blocks\BlockTypes type.
 * Texts get Español / Català / English tabs and are stored as {es, ca, en}; only Spanish is required.
 */
final class PageBlocks
{
    public const LOCALES = ['es' => 'Español', 'ca' => 'Català', 'en' => 'English'];

    public static function builder(string $name = 'blocks'): Builder
    {
        return Builder::make($name)
            ->label('Bloques de la página')
            ->helperText('Añade, ordena y edita las secciones de la página. Los textos se escriben en español, catalán e inglés; si dejas un idioma vacío se muestra el español.')
            ->blocks(self::blocks())
            ->addActionLabel('Añadir bloque')
            ->blockPickerColumns(['default' => 1, 'md' => 2])
            ->blockIcons()
            ->blockNumbers(false)
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->reorderableWithButtons()
            ->columnSpanFull();
    }

    /** @return list<Block> */
    public static function blocks(): array
    {
        return [
            self::block(BlockTypes::SERVICE_STRIP, 'Franja de servicio', Heroicon::OutlinedMegaphone, 'Franja blanca bajo la cabecera con un mensaje y las categorías destacadas.', [
                self::texts([
                    'label' => ['Etiqueta (en naranja)'],
                    'text' => ['Mensaje', 'textarea', true],
                    'featured_label' => ['Título de las categorías', 'text', false, 'Ej.: «Destacados:»'],
                ]),
                Toggle::make('show_categories')->label('Mostrar las 4 primeras categorías')->default(true),
            ]),

            self::block(BlockTypes::HERO, 'Portada (hero)', Heroicon::OutlinedSparkles, 'Gran bloque de bienvenida con título, botones, cifras y mosaico de productos.', [
                self::texts([
                    'eyebrow' => ['Antetítulo (pastilla)'],
                    'title' => ['Título principal', 'textarea', true],
                    'text' => ['Texto', 'textarea'],
                    'primary_label' => ['Texto del botón naranja'],
                    'secondary_label' => ['Texto del botón blanco'],
                    'mosaic_badge' => ['Etiqueta de la última foto del mosaico', 'text', false, 'Ej.: «Av. Meridiana 507, BCN»'],
                ]),
                Grid::make(2)->schema([
                    self::url('primary_url', 'Enlace del botón naranja'),
                    self::url('secondary_url', 'Enlace del botón blanco'),
                ]),
                Select::make('mosaic')->label('Mosaico de fotos')
                    ->options(['newest' => 'Las 4 últimas novedades', 'none' => 'Sin mosaico'])
                    ->default('newest')->required(),
                Repeater::make('stats')->label('Cifras destacadas (máx. 3)')
                    ->schema([self::texts(['value' => ['Cifra', 'text', true, 'Ej.: «+15.000»'], 'label' => ['Descripción', 'text', true]])])
                    ->maxItems(3)
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['value']['es'] ?? null)
                    ->addActionLabel('Añadir cifra'),
            ]),

            self::block(BlockTypes::CAMPAIGN, 'Campaña activa', Heroicon::OutlinedGift, 'Muestra aquí el banner de la campaña en curso (Black Friday, Navidad...). Si no hay ninguna, no se ve nada.', []),

            self::block(BlockTypes::CATEGORIES, 'Departamentos', Heroicon::OutlinedSquares2x2, 'Tarjetas de las categorías principales con la foto de su último producto.', [
                self::texts([
                    'eyebrow' => ['Antetítulo', 'text', false, 'Puedes usar [nombre_tienda].'],
                    'title' => ['Título', 'text', true],
                    'link_label' => ['Texto del enlace «ver todos»'],
                ]),
                TextInput::make('limit')->label('Número de categorías')->integer()->minValue(1)->maxValue(12)->default(6)->required(),
            ]),

            self::block(BlockTypes::PRODUCTS, 'Productos', Heroicon::OutlinedShoppingBag, 'Rejilla de productos: novedades, una categoría o productos en oferta.', [
                self::texts([
                    'title' => ['Título', 'text', true, 'Puedes usar [nombre_tienda].'],
                    'badge' => ['Etiqueta junto al título'],
                    'text' => ['Texto', 'textarea'],
                    'all_label' => ['Texto del filtro «todo»'],
                    'load_more_label' => ['Texto del botón «cargar más»'],
                    'count_text' => ['Texto del contador', 'text', false, 'Usa [mostrados] y [total]. Ej.: «Mostrando [mostrados] de [total] artículos».'],
                ]),
                Grid::make(2)->schema([
                    Select::make('source')->label('Qué productos')
                        ->options(['newest' => 'Novedades (los últimos publicados)', 'category' => 'De una categoría', 'sale' => 'En oferta (con precio tachado)'])
                        ->default('newest')->required()->live(),
                    Select::make('category_id')->label('Categoría')
                        ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (Get $get) => $get('source') === 'category')
                        ->required(fn (Get $get) => $get('source') === 'category'),
                    TextInput::make('limit')->label('Productos en total')->integer()->minValue(1)->maxValue(48)->default(12)->required(),
                    TextInput::make('initial')->label('Visibles al principio')->integer()->minValue(1)->maxValue(48)->default(8)->required()
                        ->helperText('El resto aparece con «cargar más».'),
                    Toggle::make('show_filters')->label('Mostrar los filtros de categoría')->default(true),
                    TextInput::make('anchor')->label('Ancla')->prefix('#')->maxLength(40)
                        ->helperText('Para enlazar a este bloque. La portada usa #novedades.'),
                ]),
            ]),

            self::block(BlockTypes::FEATURES, 'Banner de ventajas', Heroicon::OutlinedCheckBadge, 'Bloque crema con título, texto, enlace y hasta 3 tarjetas con icono.', [
                self::texts([
                    'badge' => ['Etiqueta', 'text', false, 'Puedes usar [nombre_tienda].'],
                    'title' => ['Título', 'textarea', true],
                    'text' => ['Texto', 'textarea'],
                    'link_label' => ['Texto del enlace'],
                ]),
                self::url('link_url', 'Enlace'),
                self::cards('cards', 'Tarjetas', 3, [
                    'title' => ['Título', 'text', true],
                    'text' => ['Texto', 'textarea'],
                ]),
            ]),

            self::block(BlockTypes::INFO_CARDS, 'Tarjetas informativas', Heroicon::OutlinedInformationCircle, 'Título con hasta 3 tarjetas grandes (ubicación, horario, envíos...).', [
                self::texts([
                    'eyebrow' => ['Antetítulo'],
                    'title' => ['Título', 'text', true],
                    'link_label' => ['Texto del enlace'],
                ]),
                self::url('link_url', 'Enlace'),
                self::cards('cards', 'Tarjetas', 3, [
                    'title' => ['Título', 'text', true],
                    'text' => ['Texto', 'textarea'],
                    'highlight_title' => ['Recuadro: línea destacada'],
                    'highlight_text' => ['Recuadro: línea secundaria'],
                    'footer' => ['Pie de la tarjeta'],
                ], withFooterIcon: true),
            ]),

            self::block(BlockTypes::CTA, 'Llamada a la acción', Heroicon::OutlinedCursorArrowRays, 'Banner centrado con título, texto y dos botones.', [
                self::texts([
                    'title' => ['Título', 'textarea', true],
                    'text' => ['Texto', 'textarea'],
                    'primary_label' => ['Texto del botón naranja'],
                    'secondary_label' => ['Texto del botón blanco'],
                ]),
                Grid::make(2)->schema([
                    self::url('primary_url', 'Enlace del botón naranja'),
                    self::url('secondary_url', 'Enlace del botón blanco'),
                    Select::make('style')->label('Estilo')->options(['navy' => 'Azul marino', 'cream' => 'Crema'])->default('navy')->required(),
                    self::icon('icon', 'Icono'),
                ]),
            ]),

            self::block(BlockTypes::IMAGE_BANNER, 'Banner con imagen', Heroicon::OutlinedPhoto, 'Foto grande con título, texto y botón encima.', [
                FileUpload::make('image')->label('Imagen')
                    ->image()->disk('public')->directory('page-blocks')->visibility('public')
                    ->maxSize(4096)->imageEditor()
                    ->helperText('Horizontal, al menos 1600 px de ancho. JPG, PNG o WebP.'),
                self::texts([
                    'title' => ['Título', 'textarea', true],
                    'text' => ['Texto', 'textarea'],
                    'button_label' => ['Texto del botón'],
                    'image_alt' => ['Descripción de la imagen (accesibilidad)', 'text', false, 'Qué se ve en la foto, para personas que no pueden verla.'],
                ]),
                self::url('button_url', 'Enlace del botón'),
            ]),

            self::block(BlockTypes::RICH_TEXT, 'Texto libre', Heroicon::OutlinedDocumentText, 'Texto con formato: títulos, listas, negritas y enlaces.', [
                self::texts([
                    'title' => ['Título'],
                    'body' => ['Texto', 'rich', true],
                ]),
                Select::make('width')->label('Ancho')->options(['narrow' => 'Estrecho (lectura)', 'wide' => 'Ancho completo'])->default('narrow')->required(),
            ]),
        ];
    }

    /**
     * Español / Català / English tabs. Field spec: name => [label, type (text|textarea|rich), required (es only), helper].
     *
     * @param  array<string, array{0: string, 1?: string, 2?: bool, 3?: string}>  $fields
     */
    public static function texts(array $fields): Tabs
    {
        $tabs = [];

        foreach (self::LOCALES as $locale => $language) {
            $schema = [];

            foreach ($fields as $name => $spec) {
                [$label, $type, $required, $helper] = $spec + [1 => 'text', 2 => false, 3 => null];
                $path = "{$name}.{$locale}";

                $field = match ($type) {
                    'textarea' => Textarea::make($path)->rows(2)->autosize(),
                    'rich' => RichEditor::make($path)->toolbarButtons([
                        ['bold', 'italic', 'underline', 'link'],
                        ['h2', 'h3', 'bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ]),
                    default => TextInput::make($path)->maxLength(255),
                };

                $schema[] = $field
                    ->label($label)
                    ->required($required && $locale === 'es')
                    ->helperText($locale === 'es' ? $helper : null)
                    ->placeholder($locale === 'es' ? null : 'Vacío = se muestra el texto en español');
            }

            $tabs[] = Tab::make($language)->schema($schema);
        }

        return Tabs::make('Textos')->tabs($tabs)->contained(false);
    }

    public static function url(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(500)
            ->rule('regex:/^(https?:\/\/|\/|#|mailto:|tel:|\[(mapa|whatsapp|email|telefono)\]$)/i')
            ->validationMessages(['regex' => 'Escribe una dirección que empiece por https://, / o #, o uno de los valores especiales.'])
            ->helperText('Ej.: /categoria/cocina, #novedades o https://… Especiales: [mapa], [whatsapp], [email], [telefono].');
    }

    public static function icon(string $name, string $label): Select
    {
        return Select::make($name)->label($label)->options(BlockTypes::ICONS)->searchable()->default('sparkles');
    }

    /** @param  array<string, array{0: string, 1?: string, 2?: bool, 3?: string}>  $texts */
    private static function cards(string $name, string $label, int $max, array $texts, bool $withFooterIcon = false): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema(array_values(array_filter([
                Grid::make(2)->schema(array_values(array_filter([
                    self::icon('icon', 'Icono'),
                    $withFooterIcon ? self::icon('footer_icon', 'Icono del pie') : null,
                ]))),
                self::texts($texts),
            ])))
            ->maxItems($max)
            ->defaultItems(0)
            ->collapsible()
            ->collapsed()
            ->itemLabel(fn (array $state) => $state['title']['es'] ?? null)
            ->addActionLabel('Añadir tarjeta');
    }

    /**
     * Form of one block type, for the visual editor side panel (state path = the block's data).
     *
     * @return list<mixed>
     */
    public static function schemaFor(string $type): array
    {
        self::$schemas = [];
        self::blocks();

        return self::$schemas[$type] ?? [];
    }

    /** "Diseño" options shared by every block: background band, spacing, devices. */
    public static function design(): Section
    {
        return Section::make('Diseño')
            ->icon(Heroicon::OutlinedSwatch)
            ->collapsible()
            ->collapsed()
            ->compact()
            ->columns(2)
            ->schema([
                Select::make('style.background')->label('Fondo de la franja')
                    ->options(['none' => 'Sin fondo (crema)', 'white' => 'Blanco', 'cream' => 'Crema oscuro', 'peach' => 'Melocotón'])
                    ->default('none')->selectablePlaceholder(false),
                Select::make('style.visibility')->label('Mostrar en')
                    ->options(['all' => 'Ordenador y móvil', 'desktop' => 'Solo ordenador', 'mobile' => 'Solo móvil'])
                    ->default('all')->selectablePlaceholder(false),
                Select::make('style.spacing_top')->label('Espacio arriba')
                    ->options(['none' => 'Pegado', 'normal' => 'Normal', 'large' => 'Grande'])
                    ->default('normal')->selectablePlaceholder(false),
                Select::make('style.spacing_bottom')->label('Espacio abajo')
                    ->options(['none' => 'Pegado', 'normal' => 'Normal', 'large' => 'Grande'])
                    ->default('normal')->selectablePlaceholder(false),
            ]);
    }

    /** @var array<string, list<mixed>> */
    private static array $schemas = [];

    /** @param  list<mixed>  $schema */
    private static function block(string $type, string $label, Heroicon $icon, string $description, array $schema): Block
    {
        $fields = [
            ...$schema,
            self::design(),
            Toggle::make('is_hidden')->label('Ocultar este bloque en la tienda')
                ->helperText('Se guarda, pero no se muestra.'),
        ];

        self::$schemas[$type] = $fields;

        return Block::make($type)
            ->label(function (?array $state) use ($label) {
                $title = Str::limit(strip_tags((string) ($state['title']['es'] ?? $state['label']['es'] ?? '')), 50);

                return $title !== '' ? "{$label} · {$title}" : $label;
            })
            ->icon($icon)
            ->schema([
                Section::make()->description($description)->compact()->schema($fields),
            ]);
    }
}
