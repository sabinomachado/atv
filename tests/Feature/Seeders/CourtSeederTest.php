<?php

use App\Models\Court;
use Database\Seeders\CourtSeeder;

test('seeds the three tournament courts with the correct active status', function () {
    $this->seed(CourtSeeder::class);

    expect(Court::count())->toBe(3);

    expect(Court::where('name', 'Coroados (Jogos)')->value('is_active'))->toBeTrue()
        ->and(Court::where('name', 'AABB')->value('is_active'))->toBeTrue()
        ->and(Court::where('name', 'Coroados (Aulas)')->value('is_active'))->toBeFalse();
});

test('running the seeder twice does not duplicate courts', function () {
    $this->seed(CourtSeeder::class);
    $this->seed(CourtSeeder::class);

    expect(Court::count())->toBe(3);
});
