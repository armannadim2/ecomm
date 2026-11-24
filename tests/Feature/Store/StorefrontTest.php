<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Content\Models\Page;
use App\Domain\Store\Settings\StoreSettings;

use function Pest\Laravel\get;

function publishedProduct(array $attributes = [], int $priceCents = 2500): Product
{
    $product = Product::factory()->sellable(priceCents: $priceCents)->create($attributes);
    app(PublishProduct::class)->handle($product);

    return $product->fresh();
}

it('shows the home page with the newest products', function () {
    $product = publishedProduct(['name' => 'Camiseta Lino']);
    Product::factory()->sellable()->create(['name' => 'Borrador Secreto']);

    get('/')->assertOk()
        ->assertSee('Camiseta Lino')
        ->assertDontSee('Borrador Secreto');
});

it('shows a category with its products and sorts by price', function () {
    $category = Category::factory()->create(['name' => 'Camisetas']);
    $cheap = publishedProduct(['name' => 'Barata'], 500);
    $expensive = publishedProduct(['name' => 'Cara'], 9000);
    $cheap->categories()->sync([$category->id]);
    $expensive->categories()->sync([$category->id]);

    get('/categoria/camisetas?orden=precio-asc')->assertOk()->assertSeeInOrder(['Barata', 'Cara']);
    get('/categoria/camisetas?orden=precio-desc')->assertOk()->assertSeeInOrder(['Cara', 'Barata']);
});

it('hides invisible categories', function () {
    Category::factory()->create(['name' => 'Oculta', 'is_visible' => false]);

    get('/categoria/oculta')->assertNotFound();
});

it('shows a published product with price, IVA note and structured data', function () {
    publishedProduct(['name' => 'Sudadera Azul'], 3999);

    get('/producto/sudadera-azul')->assertOk()
        ->assertSee('Sudadera Azul')
        ->assertSee('39,99 €')
        ->assertSee('IVA incluido')
        ->assertSee('"@type":"Product"', false)
        ->assertSee('"priceCurrency":"EUR"', false);
});

it('returns 404 for draft and deleted products', function () {
    Product::factory()->sellable()->create(['name' => 'En Borrador']);
    $deleted = publishedProduct(['name' => 'Borrado']);
    $deleted->delete();

    get('/producto/en-borrador')->assertNotFound();
    get('/producto/borrado')->assertNotFound();
});

it('strips dangerous HTML from product descriptions', function () {
    publishedProduct(['name' => 'Seguro', 'description' => '<p>Hola</p><script>alert(1)</script>']);

    get('/producto/seguro')->assertOk()->assertSee('<p>Hola</p>', false)->assertDontSee('alert(1)', false);
});

it('searches published products only', function () {
    publishedProduct(['name' => 'Zapatillas Rojas']);
    Product::factory()->sellable()->create(['name' => 'Zapatillas Borrador']);

    get('/buscar?q=zapatillas')->assertOk()
        ->assertSee('Zapatillas Rojas')
        ->assertDontSee('Zapatillas Borrador');

    get('/buscar?q=z')->assertOk()->assertSee('al menos 2 letras');
});

it('shows published legal pages with store data filled in', function () {
    $settings = app(StoreSettings::class);
    $settings->legal_name = 'Tienda Ejemplo S.L.';
    $settings->nif = 'B12345678';
    $settings->save();

    Page::query()->where('slug', 'aviso-legal')->update(['is_published' => true]);

    get('/pagina/aviso-legal')->assertOk()
        ->assertSee('Tienda Ejemplo S.L.')
        ->assertSee('B12345678')
        ->assertDontSee('[nif]');
});

it('seeds the legal pages as unpublished drafts', function () {
    expect(Page::query()->where('type', Page::TYPE_LEGAL)->count())->toBe(5)
        ->and(Page::query()->published()->count())->toBe(0);

    get('/pagina/aviso-legal')->assertNotFound();
});

it('lists products, categories and pages in the sitemap', function () {
    publishedProduct(['name' => 'Gorra Verde']);

    get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('store.product', 'gorra-verde'), false);
});

it('blocks crawlers outside production', function () {
    get('/robots.txt')->assertOk()->assertSee('Disallow: /');
});

it('sends security headers', function () {
    get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
