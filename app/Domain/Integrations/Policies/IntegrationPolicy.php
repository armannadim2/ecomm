<?php

namespace App\Domain\Integrations\Policies;

use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/** API keys and webhooks open the shop to other systems: one permission for everything (owners pass anyway). */
class IntegrationPolicy
{
    private function allowed(Staff $staff): bool
    {
        return $staff->can('integrations.manage');
    }

    public function viewAny(Staff $staff): bool
    {
        return $this->allowed($staff);
    }

    public function view(Staff $staff, Model $model): bool
    {
        return $this->allowed($staff);
    }

    public function create(Staff $staff): bool
    {
        return $this->allowed($staff);
    }

    public function update(Staff $staff, Model $model): bool
    {
        return $this->allowed($staff);
    }

    public function delete(Staff $staff, Model $model): bool
    {
        return $this->allowed($staff);
    }

    public function deleteAny(Staff $staff): bool
    {
        return $this->allowed($staff);
    }
}
