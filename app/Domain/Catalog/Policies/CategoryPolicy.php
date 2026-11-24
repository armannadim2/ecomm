<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;

class CategoryPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'categories';
    }
}
