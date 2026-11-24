<?php

namespace Database\Factories;

use App\Domain\Customers\Models\Address;
use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'line1' => fake()->streetAddress(),
            'postal_code' => fake()->numerify('28###'),
            'city' => 'Madrid',
            'province' => 'Madrid',
            'phone' => '+34 6'.fake()->numerify('## ### ###'),
            'is_default' => false,
        ];
    }
}
