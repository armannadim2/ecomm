<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Policies\PermissionPolicy;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/**
 * Full catalog permissions ("products.*") apply to every product.
 * "products.submit" (Colaborador) only reaches the staff member's own
 * products, and only while they are not live.
 */
class ProductPolicy extends PermissionPolicy
{
    protected function prefix(): string
    {
        return 'products';
    }

    public function viewAny(Staff $staff): bool
    {
        return parent::viewAny($staff) || $this->allows($staff, 'submit');
    }

    public function view(Staff $staff, Model $model): bool
    {
        return parent::view($staff, $model) || $this->ownsEditable($staff, $model, anyStatus: true);
    }

    public function create(Staff $staff): bool
    {
        return parent::create($staff) || $this->allows($staff, 'submit');
    }

    public function update(Staff $staff, Model $model): bool
    {
        return parent::update($staff, $model) || $this->ownsEditable($staff, $model);
    }

    public function delete(Staff $staff, Model $model): bool
    {
        return parent::delete($staff, $model) || $this->ownsEditable($staff, $model);
    }

    public function publish(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'publish');
    }

    public function submitForReview(Staff $staff, Product $product): bool
    {
        return ! $this->allows($staff, 'publish') && $this->ownsEditable($staff, $product);
    }

    public function adjustStock(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'stock');
    }

    public function replicate(Staff $staff, Product $product): bool
    {
        return $this->allows($staff, 'create');
    }

    /** Own product that is still a draft or waiting for review. */
    private function ownsEditable(Staff $staff, Model $product, bool $anyStatus = false): bool
    {
        /** @var Product $product */
        return $this->allows($staff, 'submit')
            && $product->isOwnedBy($staff)
            && ($anyStatus || in_array($product->status, [ProductStatus::Draft, ProductStatus::PendingReview], true));
    }
}
