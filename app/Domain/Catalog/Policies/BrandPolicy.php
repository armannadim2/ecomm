<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Shared\Policies\PermissionPolicy;

class BrandPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'brands';
    }
}
