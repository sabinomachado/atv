<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['AABB', 'Coroados - Qd de Jogo']),
            'available_days' => null,
            'is_active' => true,
        ];
    }

    /**
     * The training court, only available on weekends.
     */
    public function trainingCourt(): static
    {
        return $this->state(fn () => [
            'name' => 'Coroados - Qd de Treino',
            'available_days' => ['saturday', 'sunday'],
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
