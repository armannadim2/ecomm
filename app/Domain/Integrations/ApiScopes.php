<?php

namespace App\Domain\Integrations;

/**
 * What an API key may do. Each key gets a subset; routes check them with the `abilities:` middleware.
 * Labels are shown to the owner in "Integraciones → Claves API".
 */
final class ApiScopes
{
    public const PRODUCTS_READ = 'products:read';

    public const PRODUCTS_WRITE = 'products:write';

    public const STOCK_WRITE = 'stock:write';

    public const CATEGORIES_READ = 'categories:read';

    public const CATEGORIES_WRITE = 'categories:write';

    public const ORDERS_READ = 'orders:read';

    public const ORDERS_WRITE = 'orders:write';

    public const CUSTOMERS_READ = 'customers:read';

    /** @return array<string, string> scope => Spanish label */
    public static function labels(): array
    {
        return [
            self::PRODUCTS_READ => 'Leer productos, variantes, precios y stock',
            self::PRODUCTS_WRITE => 'Crear, editar, publicar y borrar productos',
            self::STOCK_WRITE => 'Cambiar el stock (TPV, almacén)',
            self::CATEGORIES_READ => 'Leer categorías y marcas',
            self::CATEGORIES_WRITE => 'Crear, editar y borrar categorías y marcas',
            self::ORDERS_READ => 'Leer pedidos',
            self::ORDERS_WRITE => 'Cambiar el estado de los pedidos',
            self::CUSTOMERS_READ => 'Leer clientes (datos personales: úsalo solo si hace falta)',
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    /** Ready-made sets for common integrations. @return array<string, array{label: string, scopes: list<string>}> */
    public static function presets(): array
    {
        return [
            'pos' => ['label' => 'TPV de la tienda (stock y pedidos)', 'scopes' => [
                self::PRODUCTS_READ, self::STOCK_WRITE, self::CATEGORIES_READ, self::ORDERS_READ, self::ORDERS_WRITE,
            ]],
            'catalog' => ['label' => 'Sincronizar catálogo (ERP, proveedor)', 'scopes' => [
                self::PRODUCTS_READ, self::PRODUCTS_WRITE, self::STOCK_WRITE, self::CATEGORIES_READ, self::CATEGORIES_WRITE,
            ]],
            'read' => ['label' => 'Solo lectura (informes)', 'scopes' => [
                self::PRODUCTS_READ, self::CATEGORIES_READ, self::ORDERS_READ,
            ]],
        ];
    }
}
