<?php

namespace App\Domain\Integrations;

/** Events a webhook can subscribe to. The payload `data` is the same JSON the API returns for that record. */
final class WebhookEvents
{
    public const ORDER_CREATED = 'order.created';

    public const ORDER_UPDATED = 'order.updated';

    public const PRODUCT_CREATED = 'product.created';

    public const PRODUCT_UPDATED = 'product.updated';

    public const PRODUCT_DELETED = 'product.deleted';

    public const STOCK_UPDATED = 'stock.updated';

    /** @return array<string, string> event => Spanish label */
    public static function labels(): array
    {
        return [
            self::ORDER_CREATED => 'Pedido nuevo',
            self::ORDER_UPDATED => 'Pedido actualizado (estado, pago…)',
            self::PRODUCT_CREATED => 'Producto creado',
            self::PRODUCT_UPDATED => 'Producto actualizado',
            self::PRODUCT_DELETED => 'Producto enviado a la papelera',
            self::STOCK_UPDATED => 'Stock de una variante cambiado',
        ];
    }
}
