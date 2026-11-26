<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Integrations\ApiScopes;
use App\Domain\Integrations\Jobs\SendWebhook;
use App\Domain\Integrations\Models\ApiClient;
use App\Domain\Integrations\Models\Webhook;
use App\Domain\Integrations\WebhookEvents;
use App\Domain\Orders\Mail\OrderReadyForPickup;
use App\Domain\Orders\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

/** Creates an API client and sends its key with the next requests. */
function apiAs(array $scopes = []): ApiClient
{
    $client = ApiClient::query()->create(['name' => 'TPV de prueba']);
    withToken($client->issueKey($scopes ?: ApiScopes::all())->plainTextToken);

    return $client;
}

function apiProduct(int $stock = 10, string $sku = 'TM-0001'): Product
{
    $product = Product::factory()->sellable(priceCents: 1500, stock: $stock)->create(['name' => 'Paella 28 cm']);
    $product->variants()->first()->update(['sku' => $sku, 'barcode' => '8410000000017']);
    app(PublishProduct::class)->handle($product);

    return $product->fresh('variants');
}

beforeEach(fn () => Mail::fake());

it('refuses requests without a valid API key', function () {
    getJson('/api/v1')->assertUnauthorized();

    withToken('1|not-a-real-key');
    getJson('/api/v1/products')->assertUnauthorized();
});

it('refuses customer tokens and revoked keys', function () {
    $customer = Customer::factory()->create();
    withToken($customer->createToken('app', ['*'])->plainTextToken);
    getJson('/api/v1/products')->assertUnauthorized();

    $client = ApiClient::query()->create(['name' => 'Antigua']);
    $key = $client->issueKey(ApiScopes::all())->plainTextToken;
    $client->revoke();
    withToken($key);
    getJson('/api/v1/products')->assertUnauthorized();
});

it('tells the client who it is and what it may do', function () {
    apiAs([ApiScopes::PRODUCTS_READ]);

    getJson('/api/v1')->assertOk()->assertJson(['client' => 'TPV de prueba', 'scopes' => [ApiScopes::PRODUCTS_READ]]);
});

it('checks the scopes of the key', function () {
    apiAs([ApiScopes::PRODUCTS_READ]);

    getJson('/api/v1/products')->assertOk();
    postJson('/api/v1/products', [])->assertForbidden();
    postJson('/api/v1/stock/adjust', [])->assertForbidden();
    getJson('/api/v1/orders')->assertForbidden();
    getJson('/api/v1/customers')->assertForbidden();
});

it('creates, publishes, updates and deletes products', function () {
    apiAs();
    $category = Category::factory()->create();

    $created = postJson('/api/v1/products', [
        'name' => 'Cafetera italiana 6 tazas',
        'description' => '<p>Acero inoxidable</p>',
        'category_ids' => [$category->ulid],
        'status' => 'published',
        'variants' => [
            ['sku' => 'CAF-6', 'barcode' => '8410000000024', 'price_cents' => 1850, 'stock' => 7],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.variants.0.sku', 'CAF-6')
        ->assertJsonPath('data.variants.0.stock', 7)
        ->assertJsonPath('data.categories.0.id', $category->ulid);

    $id = $created->json('data.id');
    expect(Product::query()->where('ulid', $id)->sole()->status)->toBe(ProductStatus::Published);

    patchJson("/api/v1/products/{$id}", ['name' => 'Cafetera italiana (6 tazas)'])
        ->assertOk()->assertJsonPath('data.name', 'Cafetera italiana (6 tazas)');

    postJson("/api/v1/products/{$id}/unpublish")->assertOk()->assertJsonPath('data.status', 'draft');
    deleteJson("/api/v1/products/{$id}")->assertNoContent();
    expect(Product::withTrashed()->where('ulid', $id)->sole()->trashed())->toBeTrue();
});

it('validates new products and refuses duplicated SKUs', function () {
    apiAs();
    apiProduct(sku: 'DUP-1');

    postJson('/api/v1/products', ['name' => 'Sin variantes'])->assertUnprocessable()->assertJsonValidationErrors('variants');
    postJson('/api/v1/products', ['name' => 'Repetido', 'variants' => [['sku' => 'DUP-1', 'price_cents' => 100]]])
        ->assertUnprocessable()->assertJsonValidationErrors('variants.0.sku');
});

it('refuses to publish a product that is not ready to sell', function () {
    apiAs();

    postJson('/api/v1/products', [
        'name' => 'Sin categoría', 'status' => 'published',
        'variants' => [['sku' => 'X-1', 'price_cents' => 500]],
    ])->assertUnprocessable()->assertJsonPath('message', 'Asigna al menos una categoría para que los clientes encuentren el producto.');

    expect(Product::query()->count())->toBe(0); // nothing half-created
});

it('lists products with filters and pagination', function () {
    apiAs();
    apiProduct(sku: 'A-1');
    apiProduct(sku: 'B-1');

    getJson('/api/v1/products?per_page=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 2);
    getJson('/api/v1/products?sku=B-1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.variants.0.sku', 'B-1');
    getJson('/api/v1/products?updated_since=2999-01-01T00:00:00Z')->assertOk()->assertJsonCount(0, 'data');
    getJson('/api/v1/products?updated_since=ayer')->assertUnprocessable();
});

it('finds a variant by barcode and updates its price, but not its stock', function () {
    apiAs();
    $variant = apiProduct()->variants->first();

    getJson('/api/v1/variants?barcode=8410000000017')->assertOk()->assertJsonPath('data.0.id', $variant->ulid);
    patchJson("/api/v1/variants/{$variant->ulid}", ['price_cents' => 1299])->assertOk()->assertJsonPath('data.price_cents', 1299);
    patchJson("/api/v1/variants/{$variant->ulid}", ['stock' => 99])->assertUnprocessable();
    expect($variant->fresh()->stock)->toBe(10);
});

it('adjusts and sets stock in batches, all or nothing', function () {
    apiAs();
    $a = apiProduct(stock: 5, sku: 'A-1')->variants->first();
    $b = apiProduct(stock: 1, sku: 'B-1')->variants->first();

    postJson('/api/v1/stock/adjust', ['reason' => 'Venta TPV #1', 'items' => [
        ['sku' => 'A-1', 'change' => -2],
        ['variant_id' => $b->ulid, 'change' => -1],
    ]])->assertOk()->assertJsonPath('data.0.stock', 3)->assertJsonPath('data.1.stock', 0);

    // Second line has no stock left: the first one must not be applied either.
    postJson('/api/v1/stock/adjust', ['reason' => 'Venta TPV #2', 'items' => [
        ['sku' => 'A-1', 'change' => -1],
        ['sku' => 'B-1', 'change' => -1],
    ]])->assertUnprocessable()->assertJsonPath('message', fn ($m) => str_contains($m, 'Línea 2 (B-1)'));
    expect($a->fresh()->stock)->toBe(3);

    postJson('/api/v1/stock/set', ['reason' => 'Inventario', 'items' => [['sku' => 'A-1', 'quantity' => 12]]])
        ->assertOk()->assertJsonPath('data.0.stock', 12);
    postJson('/api/v1/stock/adjust', ['items' => [['sku' => 'NO-EXISTE', 'change' => 1]]])->assertUnprocessable();
});

it('manages categories and protects the tree', function () {
    apiAs();

    $parent = postJson('/api/v1/categories', ['name' => 'Cocina'])->assertCreated()->json('data.id');
    $child = postJson('/api/v1/categories', ['name' => 'Sartenes', 'parent_id' => $parent])
        ->assertCreated()->assertJsonPath('data.parent_id', $parent)->json('data.id');

    patchJson("/api/v1/categories/{$child}", ['name' => 'Sartenes y paelleras'])->assertOk();
    patchJson("/api/v1/categories/{$parent}", ['parent_id' => $parent])->assertUnprocessable();
    deleteJson("/api/v1/categories/{$parent}")->assertUnprocessable();
    deleteJson("/api/v1/categories/{$child}")->assertNoContent();
    getJson('/api/v1/categories')->assertOk()->assertJsonCount(1, 'data');
});

it('lists orders and moves them through their statuses like the admin', function () {
    apiAs();
    $variant = apiProduct()->variants->first();
    post('/cesta', ['variant' => $variant->ulid, 'quantity' => 2]);
    post('/cesta/confirmar', [
        'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store',
        'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => 'marta@example.test', 'phone' => '612345678', 'accept_terms' => '1',
    ]);
    $order = Order::query()->sole();

    getJson('/api/v1/orders?status=confirmed')->assertOk()
        ->assertJsonPath('data.0.number', $order->number)
        ->assertJsonPath('data.0.items.0.sku', 'TM-0001')
        ->assertJsonPath('data.0.totals.total_cents', 3000);

    postJson("/api/v1/orders/{$order->ulid}/status", ['status' => 'ready_for_pickup'])->assertUnprocessable();
    postJson("/api/v1/orders/{$order->ulid}/status", ['status' => 'preparing'])->assertOk()->assertJsonPath('data.status', 'preparing');
    postJson("/api/v1/orders/{$order->ulid}/status", ['status' => 'ready_for_pickup'])->assertOk();
    Mail::assertQueued(OrderReadyForPickup::class);

    patchJson("/api/v1/orders/{$order->ulid}", ['staff_note' => 'Cliente avisado por teléfono'])
        ->assertOk()->assertJsonPath('data.staff_note', 'Cliente avisado por teléfono');
});

it('reads customers only with the customers scope', function () {
    apiAs([ApiScopes::CUSTOMERS_READ]);
    $customer = Customer::factory()->create(['email' => 'joan@example.test']);

    getJson('/api/v1/customers?email=joan@example.test')->assertOk()->assertJsonPath('data.0.id', $customer->ulid);
    getJson("/api/v1/customers/{$customer->ulid}")->assertOk()->assertJsonMissingPath('data.password');
});

it('uploads and removes product images', function () {
    Storage::fake('public');
    apiAs();
    $product = apiProduct();

    $image = postJson("/api/v1/products/{$product->ulid}/images", [
        'image' => UploadedFile::fake()->image('paella.jpg', 800, 800),
        'alt' => 'Paella de 28 cm',
    ])->assertCreated()->json('data.images.0');

    expect($image['alt'])->toBe('Paella de 28 cm');
    deleteJson("/api/v1/products/{$product->ulid}/images/{$image['id']}")->assertNoContent();

    postJson("/api/v1/products/{$product->ulid}/images", ['image' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])
        ->assertUnprocessable();
});

it('limits each key to 120 requests a minute', function () {
    apiAs([ApiScopes::PRODUCTS_READ]);

    foreach (range(1, 120) as $ignored) {
        getJson('/api/v1');
    }

    getJson('/api/v1')->assertTooManyRequests();
});

it('queues signed webhooks when stock, products or orders change', function () {
    Queue::fake();
    $webhook = Webhook::query()->create(['name' => 'TPV', 'url' => 'https://tpv.example.test/hook', 'events' => [WebhookEvents::STOCK_UPDATED]]);
    apiAs();
    apiProduct(sku: 'W-1');

    postJson('/api/v1/stock/adjust', ['items' => [['sku' => 'W-1', 'change' => -1]], 'reason' => 'Venta'])->assertOk();

    Queue::assertPushed(SendWebhook::class, fn (SendWebhook $job) => $job->webhookId === $webhook->id
        && $job->payload['event'] === WebhookEvents::STOCK_UPDATED
        && $job->payload['data']['sku'] === 'W-1'
        && $job->payload['data']['stock'] === 9);
    Queue::assertNotPushed(SendWebhook::class, fn (SendWebhook $job) => $job->payload['event'] !== WebhookEvents::STOCK_UPDATED);
});

it('delivers a webhook with an HMAC signature the receiver can check', function () {
    Http::fake(['tpv.example.test/*' => Http::response('', 200)]);
    $webhook = Webhook::query()->create(['name' => 'TPV', 'url' => 'https://tpv.example.test/hook', 'events' => [WebhookEvents::ORDER_CREATED]]);
    $payload = ['id' => '01TEST', 'event' => 'order.created', 'created_at' => now()->toIso8601String(), 'data' => ['number' => 'TM-2026-00001']];

    (new SendWebhook($webhook->id, $payload))->handle();

    Http::assertSent(function ($request) use ($webhook) {
        return $request->url() === 'https://tpv.example.test/hook'
            && $request->header('X-TotMes-Event')[0] === 'order.created'
            && $request->header('X-TotMes-Signature')[0] === 'sha256='.hash_hmac('sha256', $request->body(), $webhook->secret);
    });
    expect($webhook->fresh())->last_status->toBe(200)->consecutive_failures->toBe(0);
});

it('switches a webhook off after too many failures in a row', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $webhook = Webhook::query()->create(['name' => 'Roto', 'url' => 'https://roto.example.test', 'events' => [WebhookEvents::ORDER_CREATED]]);
    $webhook->forceFill(['consecutive_failures' => Webhook::MAX_FAILURES - 1])->save();

    expect(fn () => (new SendWebhook($webhook->id, ['id' => 'x', 'event' => 'ping', 'created_at' => '', 'data' => []]))->handle())
        ->toThrow(RuntimeException::class);
    expect($webhook->fresh()->is_active)->toBeFalse();
});
