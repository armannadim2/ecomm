<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;

/** Staff access to returns. Requests are created only by customers; staff change their status. */
class ReturnRequestPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'returns';
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
