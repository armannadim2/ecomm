<?php

namespace Database\Factories;

use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<Staff> */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(fn (Staff $staff) => $staff->assignRole($role));
    }

    public function owner(): static
    {
        return $this->withRole(Staff::OWNER_ROLE);
    }
}
