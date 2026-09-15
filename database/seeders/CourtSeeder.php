<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courts = [
            ['name' => 'Coroados (Jogos)', 'is_active' => true],
            ['name' => 'Coroados (Aulas)', 'is_active' => false],
            ['name' => 'AABB', 'is_active' => true],
        ];

        foreach ($courts as $court) {
            Court::updateOrCreate(['name' => $court['name']], $court);
        }
    }
}
