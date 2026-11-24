<?php

namespace Database\Factories;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+34 6'.fake()->numerify('## ### ###'),
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'marketing_consent_at' => null,
            'last_login_at' => null,
            'anonymized_at' => null,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
