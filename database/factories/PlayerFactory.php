<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->numerify('55##9########'),
            'google_form_id' => fake()->uuid(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Player $player) {
            if ($player->categories()->exists()) {
                return;
            }

            $player->categories()->attach(Category::factory()->create());
        });
    }
}
