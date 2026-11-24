<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use Illuminate\Auth\Events\Registered;

/**
 * Creates a customer account. Used by the storefront and (later) the mobile API.
 * Terms acceptance and marketing consent are recorded with a timestamp (GDPR).
 */
class RegisterCustomer
{
    /**
     * @param  array{first_name: string, last_name: string, email: string, password: string, phone?: ?string}  $data
     */
    public function handle(array $data, bool $marketingConsent = false): Customer
    {
        $customer = new Customer($data);
        $customer->email = mb_strtolower(trim($data['email']));
        $customer->terms_accepted_at = now();
        $customer->marketing_consent_at = $marketingConsent ? now() : null;
        $customer->save();

        event(new Registered($customer));

        return $customer;
    }
}
