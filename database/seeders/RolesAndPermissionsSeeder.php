<?php

namespace Database\Seeders;

use App\Domain\Staff\Models\Staff;
use App\Domain\Staff\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Safe to run on every deploy: creates missing permissions and roles,
 * but never overwrites a role the owner has already customised.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, Staff::GUARD);
        }

        Role::findOrCreate(Staff::OWNER_ROLE, Staff::GUARD);

        foreach (Permissions::presetRoles() as $roleName => $permissions) {
            $role = Role::query()->where(['name' => $roleName, 'guard_name' => Staff::GUARD])->first();

            if ($role === null) {
                Role::create(['name' => $roleName, 'guard_name' => Staff::GUARD])
                    ->syncPermissions($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
