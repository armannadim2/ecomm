<?php

namespace App\Domain\Staff\Policies;

use App\Domain\Staff\Models\Staff;
use Spatie\Permission\Models\Role;

/**
 * Roles are managed with a single permission. The owner role is protected:
 * it can be viewed but never edited or deleted.
 */
class RolePolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->can('roles.manage');
    }

    public function view(Staff $staff, Role $role): bool
    {
        return $staff->can('roles.manage');
    }

    public function create(Staff $staff): bool
    {
        return $staff->can('roles.manage');
    }

    public function update(Staff $staff, Role $role): bool
    {
        return $role->name !== Staff::OWNER_ROLE && $staff->can('roles.manage');
    }

    public function delete(Staff $staff, Role $role): bool
    {
        return $role->name !== Staff::OWNER_ROLE
            && $role->users()->doesntExist()
            && $staff->can('roles.manage');
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
