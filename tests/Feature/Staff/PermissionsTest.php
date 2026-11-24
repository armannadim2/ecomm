<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Staff\Models\Staff;
use App\Domain\Staff\Permissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('creates every permission and preset role', function () {
    expect(Role::query()->where('guard_name', Staff::GUARD)->pluck('name')->all())
        ->toContain(Staff::OWNER_ROLE, 'Gerente', 'Contenido', 'Almacén', 'Pedidos', 'Atención al cliente');

    foreach (Permissions::presetRoles() as $permissions) {
        expect(array_diff($permissions, Permissions::all()))->toBeEmpty();
    }
});

it('does not overwrite a role the owner has customised when seeding again', function () {
    $role = Role::findByName('Almacén', Staff::GUARD);
    $role->givePermissionTo('products.update');

    $this->seed(RolesAndPermissionsSeeder::class);

    expect($role->fresh()->hasPermissionTo('products.update'))->toBeTrue();
});

it('gives the owner every permission', function () {
    $owner = staffWithRole(Staff::OWNER_ROLE);
    $product = Product::factory()->create();

    expect($owner->can('delete', $product))->toBeTrue()
        ->and($owner->can('roles.manage'))->toBeTrue();
});

it('lets warehouse staff adjust stock but not edit or delete products', function () {
    $staff = staffWithRole('Almacén');
    $product = Product::factory()->create();

    expect($staff->can('adjustStock', $product))->toBeTrue()
        ->and($staff->can('update', $product))->toBeFalse()
        ->and($staff->can('delete', $product))->toBeFalse()
        ->and($staff->can('viewAny', Staff::class))->toBeFalse();
});

it('reserves permanent deletion for nobody, not even the owner', function () {
    $owner = staffWithRole(Staff::OWNER_ROLE);

    expect($owner->can('forceDelete', Product::factory()->create()))->toBeFalse();
});

it('stops staff from deleting their own account', function () {
    $owner = staffWithRole(Staff::OWNER_ROLE);

    expect($owner->can('delete', $owner))->toBeFalse()
        ->and($owner->can('delete', staffWithRole('Pedidos')))->toBeTrue();
});

it('stops a manager from editing an owner', function () {
    $manager = staffWithRole('Gerente');
    $manager->givePermissionTo('staff.update');

    expect($manager->can('update', staffWithRole(Staff::OWNER_ROLE)))->toBeFalse()
        ->and($manager->can('update', staffWithRole('Pedidos')))->toBeTrue();
});

it('protects the owner role from edits and deletion', function () {
    $owner = staffWithRole(Staff::OWNER_ROLE);
    $ownerRole = Role::findByName(Staff::OWNER_ROLE, Staff::GUARD);

    expect($owner->can('update', $ownerRole))->toBeFalse()
        ->and($owner->can('delete', $ownerRole))->toBeFalse()
        ->and($owner->can('update', Role::findByName('Pedidos', Staff::GUARD)))->toBeTrue();
});
