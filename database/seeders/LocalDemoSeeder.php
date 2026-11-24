<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use App\Domain\Staff\Models\Staff;
use Illuminate\Database\Seeder;

/**
 * Local development data only. Never runs outside APP_ENV=local.
 *
 * Local test logins (password: "password"):
 *   owner@ecomm.test    → Propietario
 *   almacen@ecomm.test  → Almacén
 */
class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $owner = Staff::query()->firstOrCreate(
            ['email' => 'owner@ecomm.test'],
            ['name' => 'Propietario Demo', 'password' => 'password', 'is_active' => true],
        );
        $owner->syncRoles([Staff::OWNER_ROLE]);

        $warehouse = Staff::query()->firstOrCreate(
            ['email' => 'almacen@ecomm.test'],
            ['name' => 'Empleado Almacén', 'password' => 'password', 'is_active' => true],
        );
        $warehouse->syncRoles(['Almacén']);

        if (Product::query()->exists()) {
            return;
        }

        $brands = Brand::factory()->count(3)->create();
        $categories = collect(['Camisetas', 'Pantalones', 'Accesorios'])
            ->map(fn (string $name, int $i) => Category::factory()->create(['name' => $name, 'position' => $i]));

        Product::factory()
            ->count(12)
            ->has(ProductVariant::factory()->count(2), 'variants')
            ->create()
            ->each(function (Product $product) use ($brands, $categories): void {
                $product->brand()->associate($brands->random())->save();
                $product->categories()->sync([$categories->random()->id]);
            });
    }
}
