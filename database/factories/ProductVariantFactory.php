<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductVariant> */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'price_cents' => fake()->numberBetween(500, 15000),
            'stock' => fake()->numberBetween(0, 50),
            'track_stock' => true,
            'low_stock_threshold' => 5,
        ];
    }
}
