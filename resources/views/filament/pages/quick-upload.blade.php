{{--
    "Subir producto" (App\Filament\Pages\QuickUpload).
    Layout: Stitch "Afegir Producte - Tot & Més" (D:\Projects\eComm\design\totmes-afegir-producte.html),
    translated to Spanish. Left: numbered form cards. Right: sticky live preview of the shop card + tips.
    The preview only reads the form state in the browser ($wire.data); it never sends anything.
    Variables: $categoryNames (id => name), $storeName, $canPublish
--}}
<x-filament-panels::page class="qu-page">
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        <div class="lg:col-span-8">
            {{ $this->content }}
        </div>

        <aside class="flex flex-col gap-6 lg:sticky lg:top-24 lg:col-span-4" aria-label="Vista previa">
            {{-- Live preview of the storefront card --}}
            <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                     x-data="{
                        categories: @js($categoryNames),
                        euros(value) {
                            const n = parseFloat(String(value ?? '').replace(',', '.'));
                            return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(isNaN(n) ? 0 : n);
                        },
                        get photoCount() { return Object.keys($wire.data?.photos ?? {}).length },
                     }">
                <div class="flex items-center justify-between bg-gray-900 px-4 py-2.5 text-white dark:bg-gray-950">
                    <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider">
                        <x-filament::icon icon="heroicon-o-device-phone-mobile" class="size-4 text-primary-300" />
                        Vista previa en la tienda
                    </span>
                    <span class="truncate rounded-full bg-white/10 px-2 py-0.5 text-xs text-primary-200">{{ $storeName }}</span>
                </div>

                <div class="flex flex-col gap-3 bg-gray-50 p-4 dark:bg-white/5">
                    <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-white text-gray-400 shadow-sm dark:bg-gray-800 dark:text-gray-500">
                        <div class="flex flex-col items-center gap-2 px-6 text-center">
                            <x-filament::icon icon="heroicon-o-photo" class="size-12" />
                            <span class="text-sm" x-text="photoCount ? photoCount + (photoCount === 1 ? ' foto añadida' : ' fotos añadidas') + ' · la primera será la principal' : 'Añade fotos para ver tu producto'">Añade fotos para ver tu producto</span>
                        </div>
                        <span class="absolute left-2.5 top-2.5 rounded bg-primary-600 px-2 py-0.5 text-xs font-semibold text-white shadow">NUEVO</span>
                    </div>

                    <div class="flex flex-col gap-1 pt-1">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                              x-text="categories[$wire.data?.category_id] ?? 'Sin categoría'">Sin categoría</span>
                        <h3 class="line-clamp-2 text-lg font-bold leading-tight text-gray-950 dark:text-white"
                            x-text="$wire.data?.name || 'Nombre del producto'">Nombre del producto</h3>
                        <p class="mt-1 flex items-baseline gap-2">
                            <span class="text-2xl font-extrabold tracking-tight text-primary-700 dark:text-primary-400" x-text="euros($wire.data?.price_cents)">0,00 €</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">IVA incluido</span>
                        </p>
                        <p class="mt-1 flex items-center gap-2 rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <span class="size-2.5 rounded-full bg-success-500" aria-hidden="true"></span>
                            <span><strong x-text="$wire.data?.stock || 0">1</strong> unidades disponibles</span>
                        </p>
                        <span class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 py-3 text-sm font-bold text-white shadow-sm" aria-hidden="true">
                            <x-filament::icon icon="heroicon-o-shopping-bag" class="size-4" />
                            Ver producto
                        </span>
                    </div>
                </div>
            </section>

            {{-- What happens on submit --}}
            <section @class([
                'flex items-start gap-3 rounded-xl p-4',
                'bg-success-50 dark:bg-success-500/10' => $canPublish,
                'bg-warning-50 dark:bg-warning-500/10' => ! $canPublish,
            ])>
                <x-filament::icon :icon="$canPublish ? 'heroicon-o-cloud-arrow-up' : 'heroicon-o-clock'"
                    @class(['mt-0.5 size-5 shrink-0', 'text-success-600 dark:text-success-400' => $canPublish, 'text-warning-600 dark:text-warning-400' => ! $canPublish]) />
                <p class="text-sm text-gray-700 dark:text-gray-200">
                    @if ($canPublish)
                        Al pulsar <strong>Publicar en la tienda</strong> el producto se verá en la web al momento.
                    @else
                        Al pulsar <strong>Enviar a revisión</strong> un responsable lo revisará antes de publicarlo. Te avisaremos.
                    @endif
                </p>
            </section>

            {{-- Tips --}}
            <section class="flex items-start gap-3 rounded-xl bg-gray-100 p-4 dark:bg-white/5">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="size-5" />
                </span>
                <div class="flex flex-col gap-1">
                    <h3 class="text-sm font-bold text-gray-950 dark:text-white">Consejo para el equipo</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Los artículos con fotos nítidas, un nombre claro y medidas en la descripción se venden más y reciben menos preguntas.
                        Puedes completar marca, variantes y SEO después desde <em>Productos</em>.
                    </p>
                </div>
            </section>
        </aside>
    </div>
</x-filament-panels::page>
