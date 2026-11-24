<?php

namespace App\Domain\Shared\Policies;

use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps standard abilities to "<prefix>.<action>" permissions.
 * Anything not granted is denied. Owners pass through Gate::before.
 * Permanent deletion is reserved for owners.
 */
abstract class PermissionPolicy
{
    abstract protected function prefix(): string;

    protected function allows(Staff $staff, string $action): bool
    {
        return $staff->can("{$this->prefix()}.{$action}");
    }

    public function viewAny(Staff $staff): bool
    {
        return $this->allows($staff, 'view');
    }

    public function view(Staff $staff, Model $model): bool
    {
        return $this->allows($staff, 'view');
    }

    public function create(Staff $staff): bool
    {
        return $this->allows($staff, 'create');
    }

    public function update(Staff $staff, Model $model): bool
    {
        return $this->allows($staff, 'update');
    }

    public function delete(Staff $staff, Model $model): bool
    {
        return $this->allows($staff, 'delete');
    }

    public function deleteAny(Staff $staff): bool
    {
        return $this->allows($staff, 'delete');
    }

    public function restore(Staff $staff, Model $model): bool
    {
        return $this->allows($staff, 'restore');
    }

    public function restoreAny(Staff $staff): bool
    {
        return $this->allows($staff, 'restore');
    }

    public function forceDelete(Staff $staff, Model $model): bool
    {
        return false;
    }

    public function forceDeleteAny(Staff $staff): bool
    {
        return false;
    }
}
