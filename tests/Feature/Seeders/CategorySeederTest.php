<?php

use App\Models\Category;
use Database\Seeders\CategorySeeder;

test('seeds the tournament categories', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::pluck('name')->sort()->values()->all())
        ->toEqual(['A', 'B', 'C', 'D', 'Especial', 'Feminino A', 'Feminino B']);
});

test('running the seeder twice does not duplicate categories', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe(7);
});
