<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;

/** Staff access to orders. Orders are never created or deleted in the admin. */
class OrderPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'orders';
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
