<?php

namespace App\Domain\WhatsApp\Agent;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Queries\StorefrontProducts;
use App\Domain\Content\Blocks\Block;
use App\Domain\Content\Models\Page;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Settings\CheckoutSettings;
use App\Domain\Store\Settings\StoreSettings;
use App\Domain\WhatsApp\Models\WhatsAppConversation;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Tools the WhatsApp assistant may call. All read-only, except handoff_to_human which only
 * flags the conversation. Order data is shown only to the order's own customer.
 */
class ShopTools
{
    /** Set when the assistant decided a person must take over. */
    public ?string $handoffReason = null;

    public function __construct(private readonly WhatsAppConversation $conversation) {}

    /** @return list<array<string, mixed>> */
    public static function definitions(): array
    {
        return [
            self::tool('shop_info', 'Datos de la tienda Tot & Més: dirección, horario, teléfono, correo, web, recogida en tienda, envíos, formas de pago, devoluciones, garantía y páginas legales. Úsala antes de responder cualquier pregunta sobre la tienda o sus condiciones.', []),
            self::tool('search_products', 'Busca productos publicados en la tienda online por palabras del nombre y, opcionalmente, por categoría. Devuelve hasta 6 resultados con precio, disponibilidad y enlace.', [
                'query' => ['type' => 'string', 'description' => 'Palabras a buscar, por ejemplo "sartén 24" o "taza".'],
                'category' => ['type' => ['string', 'null'], 'description' => 'Nombre de categoría para filtrar, o null.'],
            ]),
            self::tool('get_product', 'Detalle de un producto publicado (descripción, variantes, precios, disponibilidad, enlace) a partir del identificador "slug" que devuelve search_products.', [
                'slug' => ['type' => 'string', 'description' => 'Identificador del producto (campo slug de search_products).'],
            ]),
            self::tool('order_status', 'Estado de un pedido online. Necesita el número de pedido (formato TM-AAAA-NNNNN) y, si el cliente no escribe desde el teléfono del pedido, el correo electrónico o teléfono con el que se hizo. Si los datos no coinciden no devuelve nada.', [
                'order_number' => ['type' => 'string', 'description' => 'Número de pedido, p. ej. TM-2026-00012.'],
                'contact' => ['type' => ['string', 'null'], 'description' => 'Correo o teléfono del pedido que ha dado el cliente, o null.'],
            ]),
            self::tool('handoff_to_human', 'Pasa la conversación a una persona del equipo. Úsala si el cliente lo pide, para quejas, devoluciones, reembolsos, productos dañados, solicitudes de datos personales o cualquier cosa que no puedas resolver con seguridad.', [
                'reason' => ['type' => 'string', 'description' => 'Motivo breve para el equipo.'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function run(string $name, array $input): array
    {
        return match ($name) {
            'shop_info' => $this->shopInfo(),
            'search_products' => $this->searchProducts((string) ($input['query'] ?? ''), $input['category'] ?? null),
            'get_product' => $this->getProduct((string) ($input['slug'] ?? '')),
            'order_status' => $this->orderStatus((string) ($input['order_number'] ?? ''), $input['contact'] ?? null),
            'handoff_to_human' => $this->handoff((string) ($input['reason'] ?? '')),
            default => ['error' => "Herramienta desconocida: {$name}"],
        };
    }

    /** @return array<string, mixed> */
    private function shopInfo(): array
    {
        $store = app(StoreSettings::class);
        $checkout = app(CheckoutSettings::class);
        $texts = new Block('info', ['hours' => $checkout->opening_hours, 'note' => $checkout->pickup_note]);

        return [
            'nombre' => $store->store_name,
            'direccion' => $store->fullAddress(),
            'como_llegar' => Block::resolveUrl('[mapa]'),
            'telefono_y_whatsapp' => $store->phone,
            'correo' => $store->email,
            'web' => url('/'),
            'horario' => $texts->text('hours'),
            'recogida_en_tienda' => $checkout->pickup_enabled
                ? 'Gratis. Se pide en la web y se recoge en la tienda con el código de recogida del correo y un documento de identidad. '.$texts->text('note').' Guardamos el pedido 15 días.'
                : 'No disponible ahora mismo.',
            'envio_a_domicilio' => $checkout->delivery_enabled
                ? 'Península y Baleares: '.Money::format($checkout->delivery_cost_cents).', gratis desde '.Money::format($checkout->free_delivery_threshold_cents).'.'
                : 'Todavía no disponible (próximamente). De momento, recogida en tienda.',
            'formas_de_pago' => $checkout->pay_in_store_enabled
                ? 'Pago en la tienda al recoger el pedido. El pago online (tarjeta, Bizum, PayPal) estará disponible próximamente.'
                : 'Consultar con la tienda.',
            'como_comprar' => 'Por WhatsApp no se pueden hacer pedidos ni pagos: el cliente compra en la web (enlace del producto) o directamente en la tienda.',
            'devoluciones' => '14 días naturales desde la recogida o entrega para compras online (derecho de desistimiento). Lo más fácil es traerlo a la tienda. Para iniciar una devolución, pasa la conversación a una persona.',
            'garantia' => 'Garantía legal de 3 años en productos nuevos.',
            'paginas_legales' => Page::query()->published()->where('type', Page::TYPE_LEGAL)->orderBy('position')->get(['title', 'slug'])
                ->map(fn (Page $p) => ['titulo' => $p->title, 'enlace' => route('store.page', $p->slug)])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function searchProducts(string $query, ?string $category): array
    {
        $words = array_filter(preg_split('/\s+/', Str::limit(trim($query), 80, '')) ?: [], fn ($w) => mb_strlen($w) >= 2);

        $products = StorefrontProducts::query()
            ->when($words !== [], function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where('name', 'like', '%'.addcslashes($word, '%_\\').'%');
                }
            })
            ->when(filled($category), fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('is_visible', true)
                ->where('name', 'like', '%'.addcslashes((string) $category, '%_\\').'%')))
            ->latest('published_at')
            ->limit(6)
            ->get();

        return [
            'resultados' => $products->map(fn (Product $p) => [
                'slug' => $p->slug,
                'nombre' => $p->name,
                'precio_desde' => Money::format((int) $p->min_price_cents),
                'disponibilidad' => self::availability($p->variants->sum(fn ($v) => $v->track_stock ? $v->stock : 99)),
                'enlace' => route('store.product', $p->slug),
            ])->all(),
            'nota' => $products->isEmpty() ? 'Sin resultados en la tienda online. La tienda física tiene más referencias: el cliente puede preguntar en la tienda.' : null,
        ];
    }

    /** @return array<string, mixed> */
    private function getProduct(string $slug): array
    {
        $product = StorefrontProducts::query()->where('slug', $slug)->first();

        if ($product === null) {
            return ['error' => 'No existe ningún producto publicado con ese identificador.'];
        }

        return [
            'nombre' => $product->name,
            'descripcion' => Str::limit(trim(strip_tags((string) ($product->short_description ?: $product->description))), 800),
            'categorias' => $product->categories->pluck('name')->all(),
            'variantes' => $product->variants->map(fn ($v) => [
                'nombre' => $v->name ?: 'Única',
                'precio' => Money::format($v->price_cents),
                'precio_anterior' => $v->compare_at_price_cents ? Money::format($v->compare_at_price_cents) : null,
                'disponibilidad' => self::availability($v->track_stock ? $v->stock : 99),
            ])->all(),
            'enlace' => route('store.product', $product->slug),
        ];
    }

    /** @return array<string, mixed> */
    private function orderStatus(string $number, ?string $contact): array
    {
        $notFound = ['encontrado' => false, 'mensaje' => 'No hay ningún pedido con ese número y esos datos de contacto. Pide al cliente que revise el número del correo de confirmación.'];

        $order = Order::query()->with('items')->where('number', strtoupper(trim($number)))->first();
        if ($order === null || ! $this->ownsOrder($order, $contact)) {
            return $notFound;
        }

        return [
            'encontrado' => true,
            'numero' => $order->number,
            'estado' => $order->status->getLabel(),
            'pago' => $order->payment_status->getLabel(),
            'entrega' => $order->fulfillment->getLabel(),
            'fecha' => $order->placed_at?->timezone('Europe/Madrid')->format('d/m/Y H:i'),
            'listo_desde' => $order->ready_at?->timezone('Europe/Madrid')->format('d/m/Y H:i'),
            'total' => Money::format($order->total_cents),
            'articulos' => $order->items->map(fn ($i) => $i->quantity.' × '.$i->product_name.($i->variant_name ? " ({$i->variant_name})" : ''))->all(),
            'nota' => 'No des el código de recogida por WhatsApp: está en el correo de confirmación.',
        ];
    }

    /** The order's own phone (writing from it), or the email/phone the customer gave, must match. */
    private function ownsOrder(Order $order, ?string $contact): bool
    {
        $lastNine = fn (?string $phone) => substr(preg_replace('/\D/', '', (string) $phone), -9);
        $orderPhone = $lastNine($order->phone);

        if ($orderPhone !== '' && strlen($orderPhone) === 9 && $orderPhone === $lastNine($this->conversation->wa_id)) {
            return true;
        }

        $contact = trim((string) $contact);
        if ($contact === '') {
            return false;
        }

        if (str_contains($contact, '@')) {
            return mb_strtolower($contact) === mb_strtolower((string) $order->email);
        }

        return $orderPhone !== '' && strlen($orderPhone) === 9 && $lastNine($contact) === $orderPhone;
    }

    /** @return array<string, mixed> */
    private function handoff(string $reason): array
    {
        $this->handoffReason = Str::limit(trim($reason) ?: 'El asistente pide ayuda', 480);

        return ['ok' => true, 'instruccion' => 'Despídete brevemente: una persona del equipo seguirá la conversación.'];
    }

    private static function availability(int $units): string
    {
        return match (true) {
            $units <= 0 => 'agotado',
            $units <= 3 => 'últimas unidades',
            default => 'disponible',
        };
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @return array<string, mixed>
     */
    private static function tool(string $name, string $description, array $properties): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'strict' => true,
            'inputSchema' => [
                'type' => 'object',
                'properties' => (object) $properties,
                'required' => array_keys($properties),
                'additionalProperties' => false,
            ],
        ];
    }
}
