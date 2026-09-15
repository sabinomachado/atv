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

test('seeds the known phone numbers', function () {
    $this->seed(PlayerSeeder::class);

    expect(Player::where('name', 'Sabino')->firstOrFail()->phone)->toBe('24992471465')
        ->and(Player::where('name', 'Matheus Aguiar')->firstOrFail()->phone)->toBe('24999919204')
        ->and(Player::where('name', 'Raphael')->firstOrFail()->phone)->toBe('24988052308')
        ->and(Player::where('name', 'Eduardo Villares')->firstOrFail()->phone)->toBe('24992632440');
});

test('seeding a phone never overwrites one already set', function () {
    $matheus = Player::factory()->create(['name' => 'Matheus Aguiar', 'phone' => '11911112222']);

    $this->seed(PlayerSeeder::class);

    expect($matheus->fresh()->phone)->toBe('11911112222');
});
