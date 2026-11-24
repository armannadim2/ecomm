<?php

namespace App\Domain\Staff\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/**
 * Extra rules on top of permissions:
 * - only an owner may edit or remove another owner;
 * - nobody deletes their own account from the staff list.
 *
 * Owners only bypass dotted permission checks (Gate::before), not these
 * policy methods, so the rules above also apply to owners.
 */
class StaffPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'staff';
    }

    public function update(Staff $staff, Model $model): bool
    {
        /** @var Staff $model */
        if ($model->isOwner() && ! $staff->isOwner()) {
            return false;
        }

        return $staff->isOwner() || parent::update($staff, $model);
    }

    public function delete(Staff $staff, Model $model): bool
    {
        /** @var Staff $model */
        if ($staff->is($model)) {
            return false;
        }

        if ($model->isOwner() && ! $staff->isOwner()) {
            return false;
        }

        return $staff->isOwner() || parent::delete($staff, $model);
    }

    public function restore(Staff $staff, Model $model): bool
    {
        return $staff->isOwner() || $this->allows($staff, 'delete');
    }

    public function restoreAny(Staff $staff): bool
    {
        return $staff->isOwner() || $this->allows($staff, 'delete');
    }
}
