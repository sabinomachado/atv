<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Player;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TournamentMatch>
 */
class TournamentMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'player1_id' => Player::factory(),
            'player2_id' => Player::factory(),
            'court_id' => null,
            'scheduled_at' => null,
            'duration_minutes' => 90,
            'status' => TournamentMatch::STATUS_PENDING,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => TournamentMatch::STATUS_SCHEDULED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => TournamentMatch::STATUS_CANCELLED,
        ]);
    }

    /**
     * A finished match with a straight-sets result. Pass 'winner_player_id'
     * explicitly if the test needs to assert on it.
     */
    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TournamentMatch::STATUS_COMPLETED,
            'score' => [
                ['player1' => 6, 'player2' => 3],
                ['player1' => 6, 'player2' => 4],
            ],
        ]);
    }
}
