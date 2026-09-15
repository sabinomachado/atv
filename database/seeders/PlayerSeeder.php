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
     * Known phone numbers, keyed by the exact name above ("Raphinha" is
     * Raphael's nickname — the bracket lists him as "Raphael").
     */
    private const PHONES = [
        'Matheus Aguiar' => '24999919204',
        'Raphael' => '24 98805-2308',
        'Eduardo Villares' => '24 99263-2440',
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

            if (isset(self::PHONES[$name]) && blank($player->phone)) {
                $player->update(['phone' => self::PHONES[$name]]);
            }
        }
    }
}
