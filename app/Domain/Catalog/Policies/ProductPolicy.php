<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;

class ProductPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'products';
    }

    public function publish(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'publish');
    }

    public function adjustStock(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'stock');
    }

    public function replicate(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'create');
    }
}
