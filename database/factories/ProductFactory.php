<?php

namespace Database\Factories;

use App\Domain\Catalog\Enums\TaxClass;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->words(3, true)),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'tax_class' => TaxClass::General,
        ];
    }

    /** A product that passes PublishProduct checks (variant + category). */
    public function sellable(int $priceCents = 1999, int $stock = 10): static
    {
        return $this
            ->has(ProductVariant::factory()->state(['price_cents' => $priceCents, 'stock' => $stock]), 'variants')
            ->hasAttached(Category::factory(), [], 'categories');
    }
}
