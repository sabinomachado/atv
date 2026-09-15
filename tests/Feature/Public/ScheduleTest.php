<?php

use App\Models\Court;
use App\Models\TournamentMatch;

test('guests can view the public schedule', function () {
    $court = Court::factory()->create(['name' => 'AABB']);
    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->setTime(10, 0),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee($match->player1->name)
        ->assertSee($match->player2->name)
        ->assertSee('AABB');
});

test('pending matches without a scheduled time are not shown', function () {
    $pending = TournamentMatch::factory()->create();

    $this->get('/')
        ->assertOk()
        ->assertDontSee($pending->player1->name);
});

test('completed matches show the final score', function () {
    $court = Court::factory()->create();
    $match = TournamentMatch::factory()->completed()->create([
        'court_id' => $court->id,
        'scheduled_at' => now()->subDay(),
    ]);
    $match->update(['winner_player_id' => $match->player1_id]);

    $this->get('/')
        ->assertOk()
        ->assertSee('6-3, 6-4');
});

test('the schedule can be filtered by court', function () {
    $courtA = Court::factory()->create(['name' => 'Quadra A']);
    $courtB = Court::factory()->create(['name' => 'Quadra B']);

    $matchA = TournamentMatch::factory()->scheduled()->create([
        'court_id' => $courtA->id,
        'scheduled_at' => now()->addDay(),
    ]);
    $matchB = TournamentMatch::factory()->scheduled()->create([
        'court_id' => $courtB->id,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->get('/?court_id='.$courtA->id)
        ->assertOk()
        ->assertSee($matchA->player1->name)
        ->assertDontSee($matchB->player1->name);
});
