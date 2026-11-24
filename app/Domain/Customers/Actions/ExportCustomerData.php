<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Address;
use App\Domain\Customers\Models\Customer;

/**
 * GDPR right of access / portability: everything we hold about a customer,
 * in a machine-readable format. Orders are added when the orders module exists.
 */
class ExportCustomerData
{
    /** @return array<string, mixed> */
    public function handle(Customer $customer): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'email_verified_at' => $customer->email_verified_at?->toIso8601String(),
                'terms_accepted_at' => $customer->terms_accepted_at?->toIso8601String(),
                'marketing_consent_at' => $customer->marketing_consent_at?->toIso8601String(),
                'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'addresses' => $customer->addresses()->get()->map(fn (Address $a) => [
                'name' => trim("{$a->first_name} {$a->last_name}"),
                'company' => $a->company,
                'tax_id' => $a->tax_id,
                'line1' => $a->line1,
                'line2' => $a->line2,
                'postal_code' => $a->postal_code,
                'city' => $a->city,
                'province' => $a->province,
                'country' => $a->country,
                'phone' => $a->phone,
            ])->all(),
        ];
    }
}
