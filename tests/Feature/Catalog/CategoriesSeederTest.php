<?php

use App\Domain\Catalog\Models\Category;
use Database\Seeders\CategoriesSeeder;

it('seeds the category tree once, with unique slugs', function () {
    $this->seed(CategoriesSeeder::class);

    expect(Category::query()->count())->toBe(456)
        ->and(Category::query()->whereNull('parent_id')->count())->toBe(25);

    $aluminio = Category::query()->where('name', 'Aluminio')->firstOrFail();
    expect($aluminio->parent->name)->toBe('Bandejas y cuencos')
        ->and($aluminio->parent->parent->parent->name)->toBe('Droguería y limpieza');

    // Same name in two branches gets a parent-prefixed slug.
    expect(Category::query()->where('name', 'LED')->pluck('slug')->sort()->values()->all())
        ->toBe(['led', 'luces-y-lamparas-led']);

    // Staff edits are kept and nothing is duplicated on a second run.
    Category::query()->where('name', 'Deporte')->update(['is_visible' => false]);
    $this->seed(CategoriesSeeder::class);

    expect(Category::query()->count())->toBe(456)
        ->and(Category::query()->where('name', 'Deporte')->value('is_visible'))->toBeFalse();
});
