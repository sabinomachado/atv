<?php

use App\Models\Player;
use App\Models\TournamentMatch;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PlayerSeeder;
use Database\Seeders\TournamentMatchSeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->seed(PlayerSeeder::class);
});

test('seeds the 18 preliminary round pairings as pending matches', function () {
    $this->seed(TournamentMatchSeeder::class);

    expect(TournamentMatch::count())->toBe(18)
        ->and(TournamentMatch::where('status', TournamentMatch::STATUS_PENDING)->count())->toBe(18)
        ->and(TournamentMatch::whereNull('scheduled_at')->count())->toBe(18);

    $sabino = Player::where('name', 'Sabino Machado')->firstOrFail();

    expect(TournamentMatch::forPlayer($sabino->id)->exists())->toBeTrue();
});

test('running the seeder twice does not duplicate matches', function () {
    $this->seed(TournamentMatchSeeder::class);
    $this->seed(TournamentMatchSeeder::class);

    expect(TournamentMatch::count())->toBe(18);
});
