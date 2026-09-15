<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Players from the "Rodada Preliminar B/C" bracket, all entering Categoria B.
     */
    private const CATEGORY_B_PLAYERS = [
        'Raphael', 'Theo Figueiredo',
        'Leo Terra', 'Gabriel Correia',
        'Sabino', 'Bernardo Porto',
        'Euler', 'Caio Oliveira',
        'Felipe Maia', 'Anderson Gomes',
        'Flavio Maia', 'Ricardo Jorge',
        'Saulo', 'Caio Lago',
        'Bernardo Pentagna', 'Enzo Hermani',
        'Ernani', 'João Vinicius',
        'Victor Virgilio', 'Matheus Amorim',
        'Matheus Aguiar', 'Nuno Marcondes',
        'Marcelo Taveira', 'William Porto',
        'Paulo Henrique', 'Bruno Badaue',
        'Matheus Rucher', 'Rafael Bueno',
        'Rafael Mynssen', 'Ricardo Rodrigues',
        'Leonard', 'Caio Pellegrinni',
        'João Lucas', 'Fabrício Neves',
        'Marcelo Lago', 'Gilliat',
        'Eduardo Villares',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryB = Category::firstOrCreate(['name' => 'B']);

        foreach (self::CATEGORY_B_PLAYERS as $name) {
            $player = Player::firstOrCreate(['name' => $name]);
            $player->categories()->syncWithoutDetaching([$categoryB->id]);
        }
    }
}
