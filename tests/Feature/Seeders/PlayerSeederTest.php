<?php

use App\Models\Category;
use App\Models\Player;
use Database\Seeders\PlayerSeeder;

test('seeds the preliminary round players into category B', function () {
    $this->seed(PlayerSeeder::class);

    expect(Player::count())->toBe(37);

    $categoryB = Category::where('name', 'B')->firstOrFail();

    expect(Player::where('name', 'Sabino')->firstOrFail()->categories->pluck('id'))
        ->toEqual(collect([$categoryB->id]));

    expect(Player::whereHas('categories', fn ($query) => $query->where('categories.id', $categoryB->id))->count())
        ->toBe(37);
});

test('running the seeder twice does not duplicate players or category attachments', function () {
    $this->seed(PlayerSeeder::class);
    $this->seed(PlayerSeeder::class);

    expect(Player::count())->toBe(37);

    $sabino = Player::where('name', 'Sabino')->firstOrFail();

    expect($sabino->categories()->count())->toBe(1);
});
