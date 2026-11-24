<?php

use App\Domain\Staff\Models\Staff;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/** Create a staff member with the given preset role. */
function staffWithRole(string $role): Staff
{
    // fresh() loads every column, as a real request would.
    return Staff::factory()->withRole($role)->create()->fresh();
}
