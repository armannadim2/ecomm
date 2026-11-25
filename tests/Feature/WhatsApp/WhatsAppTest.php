<?php

use App\Domain\Catalog\Actions\PublishProduct;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Models\Order;
use App\Domain\Staff\Models\Staff;
use App\Domain\Store\Settings\StoreSettings;
use App\Domain\WhatsApp\Agent\AgentModel;
use App\Domain\WhatsApp\Agent\AgentTurn;
use App\Domain\WhatsApp\Agent\CustomerAgent;
use App\Domain\WhatsApp\Agent\ShopTools;
use App\Domain\WhatsApp\Jobs\AnswerWhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Domain\WhatsApp\Models\WhatsAppMessage;
use App\Domain\WhatsApp\Settings\WhatsAppSettings;
use App\Filament\Pages\ManageWhatsAppSettings;
use App\Filament\Resources\WhatsAppConversations\Pages\ViewWhatsAppConversation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

const WA_SECRET = 'test-app-secret';

/** A scripted stand-in for Claude: returns the given turns in order and records what it was sent. */
class ScriptedAgentModel implements AgentModel
{
    /** @var list<array{system: string, messages: array, tools: array}> */
    public array $calls = [];

    /** @param  list<AgentTurn>  $turns */
    public function __construct(public array $turns) {}

    public function turn(string $system, array $messages, array $tools): AgentTurn
    {
        $this->calls[] = compact('system', 'messages', 'tools');

        return array_shift($this->turns) ?? new AgentTurn('end_turn', 'Fin');
    }
}

function configureWhatsApp(): void
{
    $s = app(WhatsAppSettings::class);
    $s->enabled = true;
    $s->agent_enabled = true;
    $s->phone_number_id = '123456';
    $s->access_token = 'meta-token';
    $s->app_secret = WA_SECRET;
    $s->verify_token = 'totmes-verify';
    $s->anthropic_api_key = 'sk-ant-test';
    $s->save();
}

function scriptAgent(AgentTurn ...$turns): ScriptedAgentModel
{
    $model = new ScriptedAgentModel($turns);
    app()->instance(AgentModel::class, $model);

    return $model;
}

/** Posts a signed Meta webhook with one text message. */
function whatsappText(string $from, string $text, string $id = 'wamid.1', string $name = 'Marta')
{
    $body = json_encode(['entry' => [['changes' => [['value' => [
        'contacts' => [['profile' => ['name' => $name], 'wa_id' => $from]],
        'messages' => [['from' => $from, 'id' => $id, 'type' => 'text', 'text' => ['body' => $text]]],
    ]]]]]]);

    return call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, WA_SECRET),
    ], $body);
}

beforeEach(function () {
    configureWhatsApp();
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]])]);
});

it('answers Meta\'s verification handshake only with the right token', function () {
    get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=totmes-verify&hub_challenge=12345')->assertOk()->assertSee('12345');
    get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')->assertForbidden();
});

it('refuses webhook calls without a valid signature', function () {
    post('/api/webhooks/whatsapp', ['entry' => []], ['X-Hub-Signature-256' => 'sha256=nope'])->assertForbidden();
    expect(WhatsAppMessage::query()->count())->toBe(0);
});

it('stores a message once and queues one answer', function () {
    Queue::fake();

    whatsappText('34612345678', 'Hola, ¿abrís el domingo?')->assertOk();
    whatsappText('34612345678', 'Hola, ¿abrís el domingo?')->assertOk(); // Meta retry

    $conversation = WhatsAppConversation::query()->sole();
    expect($conversation)->wa_id->toBe('34612345678')->profile_name->toBe('Marta')
        ->and($conversation->messages()->count())->toBe(1);
    Queue::assertPushed(AnswerWhatsAppConversation::class, 1);
});

it('answers with the assistant and sends the reply through WhatsApp', function () {
    $model = scriptAgent(
        new AgentTurn('tool_use', '', [['id' => 't1', 'name' => 'shop_info', 'input' => []]], [['type' => 'tool_use', 'id' => 't1', 'name' => 'shop_info', 'input' => []]]),
        new AgentTurn('end_turn', '¡Hola Marta! Abrimos todos los días de 10:00 a 21:00.'),
    );

    whatsappText('34612345678', '¿Abrís el domingo?'); // queue is sync in tests

    $reply = WhatsAppMessage::query()->where('author', WhatsAppMessage::AUTHOR_AGENT)->sole();
    expect($reply->body)->toContain('10:00 a 21:00')->and($reply->status)->toBe('sent')->and($reply->wa_message_id)->toBe('wamid.out');

    Http::assertSent(fn ($r) => str_contains($r->url(), '/123456/messages') && $r['to'] === '34612345678' && $r->hasHeader('Authorization', 'Bearer meta-token'));

    // Second call: tool result sent back in one user message after the model's own content.
    $second = $model->calls[1]['messages'];
    expect(end($second)['content'][0])->toMatchArray(['type' => 'tool_result', 'toolUseID' => 't1'])
        ->and(json_decode(end($second)['content'][0]['content'], true)['horario'])->toContain('10:00');
    // Per-reply context is a mid-conversation system message after the customer's message.
    expect($model->calls[0]['messages'][1]['role'])->toBe('system');
});

it('hands off to a person on a refusal and stops answering', function () {
    scriptAgent(new AgentTurn('refusal', ''));

    whatsappText('34612345678', 'Algo que el modelo no responde');

    $conversation = WhatsAppConversation::query()->sole();
    expect($conversation->agent_paused)->toBeTrue()->and($conversation->status)->toBe(WhatsAppConversation::STATUS_HUMAN)
        ->and(WhatsAppMessage::query()->where('author', WhatsAppMessage::AUTHOR_AGENT)->sole()->body)
        ->toBe(app(WhatsAppSettings::class)->handoff_message);

    // While a person handles it, new messages are not answered by the assistant.
    $model = scriptAgent(new AgentTurn('end_turn', 'No debería enviarse'));
    whatsappText('34612345678', '¿Hola?', 'wamid.2');
    expect($model->calls)->toBe([]);
});

it('hands off when the assistant calls handoff_to_human', function () {
    scriptAgent(
        new AgentTurn('tool_use', '', [['id' => 't1', 'name' => 'handoff_to_human', 'input' => ['reason' => 'Quiere devolver una sartén']]], []),
        new AgentTurn('end_turn', 'Te paso con una persona del equipo.'),
    );
    $owner = staffWithRole(Staff::OWNER_ROLE);

    whatsappText('34612345678', 'Quiero devolver una sartén');

    $conversation = WhatsAppConversation::query()->sole();
    expect($conversation->handoff_reason)->toBe('Quiere devolver una sartén')->and($conversation->agent_paused)->toBeTrue()
        ->and($owner->notifications()->count())->toBe(1);
});

it('shows an order only to its own customer', function () {
    $order = Order::query()->forceCreate([
        'number' => 'TM-2026-00007', 'status' => 'confirmed', 'fulfillment' => 'pickup', 'payment_method' => 'pay_in_store',
        'payment_status' => 'pending', 'first_name' => 'Marta', 'last_name' => 'Puig', 'email' => 'marta@example.test',
        'phone' => '612 345 678', 'subtotal_cents' => 1000, 'shipping_cents' => 0, 'total_cents' => 1000, 'tax_cents' => 174,
        'savings_cents' => 0, 'locale' => 'es', 'placed_at' => now(),
    ]);

    $own = new ShopTools(WhatsAppConversation::query()->create(['wa_id' => '34612345678']));
    $stranger = new ShopTools(WhatsAppConversation::query()->create(['wa_id' => '34699999999']));

    expect($own->run('order_status', ['order_number' => 'tm-2026-00007', 'contact' => null]))->toMatchArray(['encontrado' => true, 'estado' => 'Confirmado'])
        ->and($stranger->run('order_status', ['order_number' => 'TM-2026-00007', 'contact' => null])['encontrado'])->toBeFalse()
        ->and($stranger->run('order_status', ['order_number' => 'TM-2026-00007', 'contact' => 'otra@example.test'])['encontrado'])->toBeFalse()
        ->and($stranger->run('order_status', ['order_number' => 'TM-2026-00007', 'contact' => 'MARTA@example.test'])['encontrado'])->toBeTrue()
        ->and(json_encode($own->run('order_status', ['order_number' => 'TM-2026-00007', 'contact' => null])))->not->toContain((string) $order->pickup_code ?: 'x-no-code');
});

it('finds published products only', function () {
    $product = Product::factory()->sellable(priceCents: 1990, stock: 5)->create(['name' => 'Sartén antiadherente 24 cm']);
    $product->categories()->attach(Category::factory()->create(['name' => 'Cocina']));
    app(PublishProduct::class)->handle($product);
    Product::factory()->sellable()->create(['name' => 'Sartén borrador']);

    $tools = new ShopTools(WhatsAppConversation::query()->create(['wa_id' => '34612345678']));
    $found = $tools->run('search_products', ['query' => 'sartén', 'category' => null])['resultados'];

    expect($found)->toHaveCount(1)->and($found[0])->toMatchArray(['nombre' => 'Sartén antiadherente 24 cm', 'precio_desde' => '19,90 €', 'disponibilidad' => 'disponible'])
        ->and($tools->run('get_product', ['slug' => $product->slug])['variantes'][0]['precio'])->toBe('19,90 €');
});

it('rebuilds the conversation as text, starting with the customer', function () {
    $conversation = WhatsAppConversation::query()->create(['wa_id' => '34612345678', 'last_inbound_at' => now()]);
    $conversation->messages()->createMany([
        ['direction' => 'out', 'author' => 'agent', 'body' => 'Mensaje viejo del asistente'],
        ['direction' => 'in', 'author' => 'customer', 'body' => 'Hola'],
        ['direction' => 'in', 'author' => 'customer', 'body' => '¿Tenéis tazas?'],
    ]);
    $model = scriptAgent(new AgentTurn('end_turn', 'Sí'));

    app(CustomerAgent::class)->reply($conversation);

    $messages = $model->calls[0]['messages'];
    expect($messages[0])->toBe(['role' => 'user', 'content' => "Hola\n¿Tenéis tazas?"])
        ->and($messages)->toHaveCount(2)
        ->and($model->calls[0]['tools'])->toHaveCount(5);
});

it('lets staff take over and reply from the admin', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $conversation = WhatsAppConversation::query()->create(['wa_id' => '34612345678', 'last_inbound_at' => now()]);

    Livewire::test(ViewWhatsAppConversation::class, ['record' => $conversation->getRouteKey()])
        ->callAction('reply', ['body' => 'Hola, soy Laura de la tienda.'])
        ->assertNotified('Mensaje enviado');

    expect($conversation->fresh()->agent_paused)->toBeTrue()
        ->and(WhatsAppMessage::query()->where('author', 'staff')->sole()->body)->toBe('Hola, soy Laura de la tienda.');
});

it('refuses to message a customer after the 24-hour window', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);
    $conversation = WhatsAppConversation::query()->create(['wa_id' => '34612345678', 'last_inbound_at' => now()->subDays(2)]);

    Livewire::test(ViewWhatsAppConversation::class, ['record' => $conversation->getRouteKey()])->assertActionDisabled('reply');
    Http::assertNothingSent();
});

it('keeps saved secrets when the settings form leaves them empty', function () {
    Filament::setCurrentPanel('admin');
    actingAs(staffWithRole(Staff::OWNER_ROLE), Staff::GUARD);

    Livewire::test(ManageWhatsAppSettings::class)
        ->assertFormSet(['access_token' => null, 'anthropic_api_key' => null])
        ->fillForm(['instructions' => 'Sé breve.', 'current_password' => 'password'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(WhatsAppSettings::class)->refresh();
    expect($settings->instructions)->toBe('Sé breve.')->and($settings->access_token)->toBe('meta-token')->and($settings->anthropic_api_key)->toBe('sk-ant-test');
});

it('shows the WhatsApp button in the shop', function () {
    $store = app(StoreSettings::class);
    $store->phone = '657 21 58 53';
    $store->save();

    get('/')->assertOk()->assertSee('https://wa.me/34657215853?text=', false);
});
