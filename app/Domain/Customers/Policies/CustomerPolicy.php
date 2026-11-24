<?php

namespace App\Domain\Customers\Policies;

use App\Domain\Customers\Models\Customer;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/**
 * Staff can look up customers; nobody edits or deletes them from the admin.
 * Customers manage (and delete) their own account from the storefront.
 */
class CustomerPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return $staff->can('customers.view');
    }

    public function view(Staff $staff, Model $customer): bool
    {
        return $staff->can('customers.view');
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function update(Staff $staff, Customer $customer): bool
    {
        return false;
    }

    public function delete(Staff $staff, Customer $customer): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
