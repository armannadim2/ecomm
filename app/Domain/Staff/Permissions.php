<?php

namespace App\Domain\Staff;

/**
 * Single list of every permission in the admin panel, with Spanish labels,
 * and the preset roles offered to the shop owner.
 *
 * Add a permission here, then run `php artisan db:seed --class=RolesAndPermissionsSeeder`.
 */
final class Permissions
{
    /** @return array<string, array{label: string, permissions: array<string, string>}> */
    public static function groups(): array
    {
        return [
            'products' => [
                'label' => 'Productos',
                'permissions' => [
                    'products.view' => 'Ver todos los productos',
                    'products.submit' => 'Subir productos propios (se revisan antes de publicarse si no puede publicar)',
                    'products.create' => 'Crear productos',
                    'products.update' => 'Editar productos',
                    'products.publish' => 'Publicar / ocultar productos',
                    'products.stock' => 'Ajustar stock',
                    'products.delete' => 'Enviar productos a la papelera',
                    'products.restore' => 'Restaurar productos de la papelera',
                ],
            ],
            'categories' => [
                'label' => 'Categorías',
                'permissions' => [
                    'categories.view' => 'Ver categorías',
                    'categories.create' => 'Crear categorías',
                    'categories.update' => 'Editar categorías',
                    'categories.delete' => 'Enviar categorías a la papelera',
                    'categories.restore' => 'Restaurar categorías',
                ],
            ],
            'brands' => [
                'label' => 'Marcas',
                'permissions' => [
                    'brands.view' => 'Ver marcas',
                    'brands.create' => 'Crear marcas',
                    'brands.update' => 'Editar marcas',
                    'brands.delete' => 'Enviar marcas a la papelera',
                    'brands.restore' => 'Restaurar marcas',
                ],
            ],
            'customers' => [
                'label' => 'Clientes',
                'permissions' => [
                    'customers.view' => 'Ver clientes',
                ],
            ],
            'orders' => [
                'label' => 'Pedidos',
                'permissions' => [
                    'orders.view' => 'Ver pedidos',
                    'orders.update' => 'Cambiar el estado de los pedidos y cancelarlos',
                ],
            ],
            'returns' => [
                'label' => 'Devoluciones y cambios',
                'permissions' => [
                    'returns.view' => 'Ver las solicitudes de devolución y cambio',
                    'returns.update' => 'Aprobar, rechazar y dar por recibidas o reembolsadas las devoluciones',
                ],
            ],
            'integrations' => [
                'label' => 'Integraciones',
                'permissions' => [
                    'integrations.manage' => 'Gestionar claves API y webhooks (TPV, ERP, otras webs)',
                ],
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'permissions' => [
                    'whatsapp.view' => 'Ver las conversaciones de WhatsApp',
                    'whatsapp.reply' => 'Responder por WhatsApp y atender en lugar del asistente',
                    'whatsapp.manage' => 'Configurar WhatsApp y el asistente (claves de Meta y Anthropic)',
                ],
            ],
            'pages' => [
                'label' => 'Páginas',
                'permissions' => [
                    'pages.view' => 'Ver páginas',
                    'pages.create' => 'Crear páginas',
                    'pages.update' => 'Editar páginas (incluidos textos legales)',
                    'pages.delete' => 'Enviar páginas a la papelera',
                    'pages.restore' => 'Restaurar páginas',
                ],
            ],
            'campaigns' => [
                'label' => 'Campañas',
                'permissions' => [
                    'campaigns.view' => 'Ver campañas',
                    'campaigns.create' => 'Crear campañas',
                    'campaigns.update' => 'Editar campañas',
                    'campaigns.delete' => 'Enviar campañas a la papelera',
                    'campaigns.restore' => 'Restaurar campañas',
                ],
            ],
            'staff' => [
                'label' => 'Equipo',
                'permissions' => [
                    'staff.view' => 'Ver empleados',
                    'staff.create' => 'Añadir empleados',
                    'staff.update' => 'Editar empleados',
                    'staff.delete' => 'Eliminar empleados',
                    'roles.manage' => 'Gestionar roles y permisos',
                ],
            ],
            'system' => [
                'label' => 'Sistema',
                'permissions' => [
                    'activity.view' => 'Ver historial de actividad',
                    'settings.manage' => 'Editar datos de la tienda',
                ],
            ],
        ];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    public static function label(string $permission): string
    {
        foreach (self::groups() as $group) {
            if (isset($group['permissions'][$permission])) {
                return $group['permissions'][$permission];
            }
        }

        return $permission;
    }

    /**
     * Preset roles. The owner role gets everything through Gate::before,
     * so it needs no explicit list.
     *
     * @return array<string, list<string>>
     */
    public static function presetRoles(): array
    {
        $catalogAll = array_values(array_filter(
            self::all(),
            fn (string $p) => str_starts_with($p, 'products.')
                || str_starts_with($p, 'categories.')
                || str_starts_with($p, 'brands.'),
        ));

        $pagesEdit = ['pages.view', 'pages.create', 'pages.update'];
        $campaignsEdit = ['campaigns.view', 'campaigns.create', 'campaigns.update'];

        return [
            'Gerente' => [
                ...$catalogAll, ...$pagesEdit, 'pages.delete', 'pages.restore',
                ...$campaignsEdit, 'campaigns.delete', 'campaigns.restore',
                'customers.view', 'staff.view', 'activity.view', 'settings.manage',
                'orders.view', 'orders.update',
                'returns.view', 'returns.update',
                'whatsapp.view', 'whatsapp.reply',
            ],
            'Contenido' => [
                'products.view', 'products.create', 'products.update', 'products.publish',
                'categories.view', 'categories.create', 'categories.update',
                'brands.view', 'brands.create', 'brands.update',
                ...$pagesEdit,
                ...$campaignsEdit,
            ],
            // Uploads products into the store; an owner/manager reviews them before they go live.
            'Colaborador' => ['products.submit', 'categories.view', 'brands.view'],
            'Almacén' => ['products.view', 'products.stock', 'categories.view', 'brands.view'],
            'Pedidos' => ['products.view', 'categories.view', 'brands.view', 'customers.view', 'orders.view', 'orders.update', 'returns.view', 'returns.update', 'whatsapp.view', 'whatsapp.reply'],
            'Atención al cliente' => ['products.view', 'categories.view', 'brands.view', 'customers.view', 'orders.view', 'returns.view', 'whatsapp.view', 'whatsapp.reply'],
        ];
    }
}
