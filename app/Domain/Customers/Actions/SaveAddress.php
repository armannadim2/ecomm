<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Address;
use App\Domain\Customers\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates an address. Keeps exactly one default address:
 * the first address is always the default.
 */
class SaveAddress
{
    /** @param array<string, mixed> $data */
    public function handle(Customer $customer, array $data, ?Address $address = null): Address
    {
        return DB::transaction(function () use ($customer, $data, $address): Address {
            $address ??= new Address;
            $address->fill($data);
            $address->customer()->associate($customer);

            if (! $customer->addresses()->whereKeyNot($address->getKey() ?? 0)->exists()) {
                $address->is_default = true;
            }

            $address->save();

            if ($address->is_default) {
                $customer->addresses()->whereKeyNot($address->getKey())->update(['is_default' => false]);
            }

            return $address;
        });
    }
}
