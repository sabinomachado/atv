<?php

namespace Database\Factories;

use App\Models\BlockedSlot;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedSlot>
 */
class BlockedSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 weeks');

        return [
            'court_id' => Court::factory(),
            'start_datetime' => $start,
            'end_datetime' => (clone $start)->modify('+2 hours'),
            'reason' => fake()->sentence(3),
        ];
    }
}
