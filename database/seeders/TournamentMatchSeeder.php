<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Player;
use App\Models\TournamentMatch;
use Illuminate\Database\Seeder;

class TournamentMatchSeeder extends Seeder
{
    /**
     * "Rodada Preliminar B/C" pairings from the tournament bracket graphic.
     * Winners stay in Categoria B, losers move to Categoria C — that split
     * happens later, by hand, once results are known.
     */
    private const CATEGORY_B_PAIRINGS = [
        ['Raphael', 'Theo Figueiredo'],
        ['Leo Terra', 'Gabriel Correia'],
        ['Sabino', 'Bernardo Porto'],
        ['Euler', 'Caio Oliveira'],
        ['Felipe Maia', 'Anderson Gomes'],
        ['Flavio Maia', 'Ricardo Jorge'],
        ['Saulo', 'Caio Lago'],
        ['Bernardo Pentagna', 'Enzo Hermani'],
        ['Ernani', 'João Vinicius'],
        ['Victor Virgilio', 'Matheus Amorim'],
        ['Matheus Aguiar', 'Nuno Marcondes'],
        ['Marcelo Taveira', 'William Porto'],
        ['Paulo Henrique', 'Bruno Badaue'],
        ['Matheus Rucher', 'Rafael Bueno'],
        ['Rafael Mynssen', 'Ricardo Rodrigues'],
        ['Leonard', 'Caio Pellegrinni'],
        ['João Lucas', 'Fabrício Neves'],
        ['Marcelo Lago', 'Gilliat'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryB = Category::where('name', 'B')->firstOrFail();

        foreach (self::CATEGORY_B_PAIRINGS as [$player1Name, $player2Name]) {
            $player1 = Player::where('name', $player1Name)->firstOrFail();
            $player2 = Player::where('name', $player2Name)->firstOrFail();

            TournamentMatch::firstOrCreate([
                'category_id' => $categoryB->id,
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
            ], [
                'status' => TournamentMatch::STATUS_PENDING,
                'duration_minutes' => 90,
            ]);
        }
    }
}
