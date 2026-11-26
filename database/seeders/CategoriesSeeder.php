<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Http\Middleware\PageCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The shop's category tree (Spanish names), up to four levels.
 *
 * Safe to run again: a category that already exists under the same parent with the same
 * name (also a deleted one) is left as it is, so staff edits are kept and only missing
 * categories are added. Rows are inserted directly so the page cache is dropped once,
 * not once per category.
 *
 * php artisan db:seed --class=CategoriesSeeder --force
 */
class CategoriesSeeder extends Seeder
{
    /** Name => children for a branch, plain name for a leaf. */
    public const TREE = [
        'Accesorios y moda' => [
            'Bolsas' => [
                'Bolsa',
                'Bolsa plegable',
            ],
            'Bolsos' => [
                'Bolso',
                'Bolso cesta',
                'Bolso de mano',
            ],
            'Carteras y monederos' => [
                'Cartera',
                'Cartera joyero',
                'Monedero',
            ],
            'Delantales',
            'Estuches',
            'Mochilas',
            'Pinzas',
            'Riñoneras',
            'Sombreros y viseras' => [
                'Sombrero',
                'Visera',
            ],
            'Toallas de mano y playa',
        ],
        'Audio y video',
        'Baño' => [
            'Accesorios de baño',
            'Alfombras baño',
            'Botiquín',
            'Cestas de baño',
            'Cortinas de ducha',
            'Cubos de baño',
            'Dispensadores de jabón',
            'Escobillas',
            'Espejos',
            'Estanterías y otros accesorios',
            'Ganchos',
            'Jaboneras' => [
                'Jabón y jabonera',
            ],
            'Juego de baño',
            'Limpieza',
            'Muebles de baño',
            'Porta cepillos',
            'Tapas WC',
            'Toallas',
        ],
        'Belleza' => [
            'Accesorios de belleza',
            'Cejas y pestañas',
            'Coleteros',
            'Desodorantes y lacas',
            'Diademas',
            'Higiene personal',
            'Jabones de manos',
            'Llaveros',
            'Neceseres',
            'Organizadores belleza',
            'Perfumes',
            'Pintauñas',
            'Pinzas de pelo',
            'Uñas y accesorios',
            'Utensilios para maquillaje',
        ],
        'Bricolaje y ferretería' => [
            'Accesorios',
            'Adhesivos y protección laboral',
            'Alicates',
            'Bricolaje',
            'Brochas, pinturas y rodillos',
            'Candados',
            'Cuerdas',
            'Destornilladores',
            'Electricidad',
            'Enchufes',
            'Guantes y linternas',
            'Herramientas',
            'Llaves',
            'Papel pintado',
            'Pintura spray',
            'Ruedas',
            'Seguridad del hogar',
        ],
        'Carnaval y Halloween' => [
            'Accesorios carnaval',
            'Conjuntos',
            'Corbatas',
            'Disfraces',
            'Muñecos',
            'Pañuelos',
            'Pelucas',
            'Sombreros y cascos',
            'Textiles',
            'Tutus',
            'Velos y diademas',
        ],
        'Cocina, menaje y mesa' => [
            'Azucareros y saleros' => [
                'Azucarero',
            ],
            'Bandejas y organizadores' => [
                'Bandeja',
            ],
            'Botellas y termos' => [
                'Botella',
            ],
            'Botes',
            'Café y té' => [
                'Cafetera',
                'Tetera',
                'Taza con filtro',
            ],
            'Coladores',
            'Complementos de cocina',
            'Cubería' => [
                'Cuchara',
                'Cucharillas',
                'Set cubiertos',
            ],
            'Cubos y limpieza',
            'Cuchillos' => [
                'Cuchillo',
            ],
            'Ensaladeras y servidores' => [
                'Ensaladera',
                'Servidores de ensalada',
            ],
            'Escurridores y estropajeros',
            'Especieros, aceiteras y vinagreras',
            'Fiambreras',
            'Ibili',
            'Jarras' => [
                'Jarra',
            ],
            'Mesa y mantelía' => [
                'Camino de mesa',
                'Mantel',
                'Servilletas',
            ],
            'Portarrollos y servilleteros',
            'Ralladores y peladores',
            'Repostería' => [
                'Cake',
            ],
            'Sartenes, ollas y paelleras' => [
                'Sartén',
                'Tajine',
            ],
            'Tablas de cocina' => [
                'Tabla',
            ],
            'Tazas' => [
                'Taza',
                'Mug',
                'Set de tazas y platos',
            ],
            'Textil cocina',
            'Tuppers y tarros' => [
                'Tarro',
                'Tupper',
            ],
            'Utensilios cocina' => [
                'Bombillas',
                'Cazo',
                'Espumadera',
                'Exprimidor',
                'Set cocina',
                'Tapón',
                'Tijeras',
            ],
            'Vajillas' => [
                'Plato',
                'Plato japonés',
                'Bowl',
                'Bowl con asas',
                'Bowl japonés',
                'Cuencos',
                'Vajilla',
            ],
            'Vasos y copas' => [
                'Vaso',
                'Copa',
            ],
            'Verduleros',
        ],
        'Decoración' => [
            'Cajas y cestas' => [
                'Caja',
                'Caja de metal',
                'Cajas de dientes',
                'Cesta de mimbre',
                'Contenedor',
            ],
            'Centros de mesa',
            'Cerámica',
            'Colgantes y colgadores' => [
                'Casa de lana',
                'Colgadores',
                'Colgante de lana',
                'Decoración colgante',
            ],
            'Cuadros y lienzos',
            'Decoración pared',
            'Espejos decorativos',
            'Figuras' => [
                'Mariposa',
                'Oso',
                'Peluche',
            ],
            'Jarrones' => [
                'Jarrón',
            ],
            'Lámparas' => [
                'LED',
            ],
            'Muebles' => [
                'Mueble',
            ],
            'Portafotos' => [
                'Marco fotos',
            ],
            'Relojes y despertadores',
            'Sillas',
            'Tarjeteros',
            'Topes de puerta',
        ],
        'Deporte',
        'Droguería y limpieza' => [
            'Conservación de alimentos y moldes',
            'Desechables' => [
                'Bandejas y cuencos' => [
                    'Aluminio',
                    'Cartón',
                ],
                'Palillos y pajitas',
            ],
        ],
        'Fiesta' => [
            'Accesorios de fiesta',
            'Bandejas de cartón y aluminio',
            'Barra de maquillaje',
            'Bolsas de regalo',
            'Complementos',
            'Coronas y bandas',
            'Cubiertos plásticos',
            'Decoración cumpleaños',
            'Desechables',
            'Globos',
            'Guirnaldas decorativas',
            'Photocall',
            'Piñatas',
            'Platos infantiles',
            'Platos plásticos',
            'Regalos',
            'Servilletas',
            'Vasos y copas plásticos',
            'Vasos, platos y manteles',
            'Velas cumpleaños',
        ],
        'Infantil' => [
            'Bebé',
            'Botellas',
            'Cajas y huchas',
            'Mochilas y paraguas',
            'Platos, sandwicheras y tazas',
        ],
        'Jardín y plantas' => [
            'Accesorios de jardín' => [
                'Comedero',
                'Invernadero',
            ],
            'Flores artificiales' => [
                'Flor artificial',
                'Flores',
            ],
            'Maceteros' => [
                'Macetero',
            ],
            'Piedras decorativas',
            'Plantas artificiales' => [
                'Cactus',
                'Fruta decorativa',
                'Planta decorativa',
            ],
        ],
        'Juguetería',
        'Marcas' => [
            'Pip Studio',
            'SMEG',
        ],
        'Mascota' => [
            'Acuario',
            'Bebedores y comederos',
            'Collares y arneses',
            'Empapadores y pañales',
            'Juguetes mascota',
            'Limpieza y otros',
            'Snacks, huesos y otros',
            'Transportines y jaulas',
        ],
        'Mini electrónica' => [
            'Accesorios de electrónica',
            'Aparatos eléctricos',
            'Auriculares y altavoces',
            'Bombillas',
            'Cable y cargador',
            'Cables',
            'Para coche',
            'Ratones y almohadillas',
        ],
        'Navidad' => [
            'Adornos de árbol y colgantes' => [
                'Adorno árbol',
                'Adorno cascabel',
                'Corona navidad',
            ],
            'Árboles y alfombras' => [
                'Árbol navidad',
                'Alfombra',
            ],
            'Bolas decorativas' => [
                'Bola árbol',
                'Bola agua',
                'Bolas de navidad',
            ],
            'Calendario de adviento',
            'Cintas y lazos' => [
                'Cinta',
                'Cintas',
            ],
            'Complementos belén',
            'Decoración de mesa' => [
                'Camino de mesa',
                'Mantel',
            ],
            'Decoración pascua',
            'Feliz año nuevo',
            'Flores navideñas' => [
                'Flores',
            ],
            'Luces y lámparas' => [
                'LED',
            ],
            'Muñecos y figuras' => [
                'Caballito',
                'Estrellas',
                'Figura navidad',
                'Figuras',
                'Muñeco perro',
                'Oso',
                'Peluche',
                'Reno',
            ],
            'Regalos y otros' => [
                'Caja',
                'Caja de música',
                'Expositor',
                'Manta',
            ],
            'Vasos y platos' => [
                'Plato',
                'Taza',
                'Vajilla',
            ],
            'Velas y portavelas' => [
                'Cofre aromas',
                'Varillas',
                'Velas',
            ],
        ],
        'Ofertas / Outlet',
        'Organización' => [
            'Accesorio de organización',
            'Cestas organizadoras',
        ],
        'Papelería' => [
            'Accesorios de papelería' => [
                'Calculadoras',
                'Calendarios y agendas',
                'Celo y portacelo',
                'Chinchetas, clips y más',
                'Compases',
                'Correctores',
                'Estuches',
                'Etiquetas adhesivas',
                'Fichas',
                'Gomas de borrar',
                'Grapadoras',
                'Juegos de regla',
                'Papel y notas',
                'Pegamentos',
                'Pequeños materiales',
                'Pizarras y accesorios',
                'Protección y embalaje',
                'Sacapuntas',
                'Sobres y embalaje',
                'Tacos de notas',
                'Talonarios',
                'Tijeras y lupas',
            ],
            'Bellas artes' => [
                'Block de dibujo',
                'Caballetes y paletas',
                'Lienzos',
                'Pinceles',
                'Pintura acrílica',
                'Pintura acuarela y témpera',
                'Pintura óleo',
                'Pintura textil',
                'Pintura tiza',
                'Pintura vidrio y cerámica',
            ],
            'Carpetas' => [
                'Carpetas clásicas',
                'Carpetas con pinza',
                'Carpetas de anillas',
                'Carpetas de fundas',
                'Carpetas fuelle',
                'Carpetas sobre',
                'Maletín',
            ],
            'Cuadernos' => [
                'Cuadernos de tapa blanda',
                'Cuadernos de tapa dura',
                'Oxford',
                'Recambios',
            ],
            'Escritura' => [
                'Artix',
                'Bic',
                'Bolígrafos',
                'Edding',
                'Lápices y portaminas',
                'Marcadores',
                'Milan',
                'Pilot',
                'Rubio',
                'Stabilo',
                'Staedtler',
                'Subrayadores',
            ],
            'Manualidades' => [
                'Abalorios',
                'Bolas porex',
                'Cuerdas y cadenas',
                'Hama beads',
                'Letras',
                'Limpiapipas',
                'Palos, tablas e imán',
                'Papel multifunción',
                'Pasta modelar',
                'Pegatinas',
                'Silicona y pegamento',
            ],
            'Material infantil' => [
                'Ceras',
                'Lápices de colores',
                'Packs de témperas y acuarelas',
                'Rotuladores',
            ],
            'Oficina' => [
                'Bandejas de oficina',
                'Organizadores de escritorio',
            ],
        ],
        'San Valentín',
        'Textil y merería' => [
            'Alfombras y felpudos' => [
                'Alfombra',
                'Alfombras',
            ],
            'Cojines' => [
                'Cojín decorativo',
            ],
            'Cortinas',
            'Costurero',
            'Hilos',
            'Lanas' => [
                'Arya',
                'Bamboo',
                'Bebé',
                'Chainy',
                'Cool',
                'Cotton / algodón',
                'Clásicas',
                'Crochet',
                'Fantasía',
                'Finas',
                'Gruesa',
                'Mediana',
                'Perlés',
                'Piri',
                'Sweet',
                'Trenzado',
                'Verano',
            ],
            'Mantas',
            'Otros accesorios',
            'Ropa cama',
        ],
        'Velas, ambientadores y fragancias' => [
            'Ambientadores' => [
                'Ambientadores de armario',
                'Ambientadores de coche',
                'Ambientadores hogar',
                'Aromatizador',
            ],
            'Cerillas',
            'Humidificadores y aceites',
            'Inciensos y conos',
            'Mikados y varillas',
            'Sobres de aromas',
            'Sprays aromáticos',
            'Sprays especiales',
            'Velas aromáticas' => [
                'Velas',
            ],
        ],
        'Verano' => [
            'Complementos de verano',
            'Hinchadores',
            'Inflables' => [
                'Colchonetas y flotadores',
                'Inflables infantiles',
            ],
            'Juguetes' => [
                'Palas, cubos y más',
                'Pistolas de agua',
            ],
            'Piscinas' => [
                'Accesorios de natación',
                'Mantenimiento y otros accesorios',
                'Piscinas hinchables y tubulares',
            ],
        ],
    ];

    private int $created = 0;

    public function run(): void
    {
        DB::transaction(fn () => $this->insert(self::TREE, null, null));

        if ($this->created > 0) {
            PageCache::flush();
            foreach (array_keys((array) config('app.available_locales')) as $locale) {
                Cache::forget('store.footer.'.$locale);
            }
            Cache::forget('sitemap.xml');
        }

        $this->command?->info("Categorías creadas: {$this->created}.");
    }

    /** @param  array<int|string, mixed>  $nodes */
    private function insert(array $nodes, ?int $parentId, ?string $parentSlug): void
    {
        $position = 0;

        foreach ($nodes as $key => $value) {
            [$name, $children] = is_array($value) ? [(string) $key, $value] : [(string) $value, []];

            $existing = DB::table('categories')
                ->where('parent_id', $parentId)
                ->where('name', $name)
                ->first(['id', 'slug']);

            if ($existing !== null) {
                [$id, $slug] = [(int) $existing->id, (string) $existing->slug];
            } else {
                $slug = $this->slugFor($name, $parentSlug);
                $id = (int) DB::table('categories')->insertGetId([
                    'ulid' => (string) Str::ulid(),
                    'parent_id' => $parentId,
                    'name' => $name,
                    'slug' => $slug,
                    'position' => $position,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->created++;
            }

            $position++;

            if ($children !== []) {
                $this->insert($children, $id, $slug);
            }
        }
    }

    /** The name's own slug; when another category has it («LED», «Oso»…), prefixed with the parent's. */
    private function slugFor(string $name, ?string $parentSlug): string
    {
        $slug = Str::slug($name);

        if ($parentSlug !== null && DB::table('categories')->where('slug', $slug)->exists()) {
            $slug = $parentSlug.'-'.$slug;
        }

        return Category::uniqueSlug($slug);
    }
}
