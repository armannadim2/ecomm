<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * GDPR right to erasure. Personal data is removed at once; the row is kept
 * (anonymised, soft-deleted) so future orders and invoices stay consistent
 * for the legally required retention period.
 */
class DeleteCustomerAccount
{
    public function handle(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $customer->addresses()->delete();
            $customer->tokens()->delete();

            $customer->forceFill([
                'first_name' => 'Cliente',
                'last_name' => 'eliminado',
                'email' => "deleted-{$customer->ulid}@invalid.local",
                'phone' => null,
                'saved_cart' => null,
                'password' => Str::random(64),
                'marketing_consent_at' => null,
                'remember_token' => null,
                'anonymized_at' => now(),
            ])->save();

            $customer->delete();
        });
    }
}
