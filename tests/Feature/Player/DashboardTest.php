<?php

use App\Livewire\PlayerDashboard;
use App\Models\Court;
use App\Models\Player;
use App\Models\TournamentMatch;
use Carbon\Carbon;
use Livewire\Livewire;

test('dashboard only shows the logged-in players pending matches', function () {
    $player = Player::factory()->create();
    $opponent = Player::factory()->create();
    TournamentMatch::factory()->create(['player1_id' => $player->id, 'player2_id' => $opponent->id]);
    $otherMatch = TournamentMatch::factory()->create();

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->assertSee($opponent->name)
        ->assertDontSee($otherMatch->player1->name);
});

test('selecting a court and date shows available slots', function () {
    $player = Player::factory()->create();
    $match = TournamentMatch::factory()->create(['player1_id' => $player->id]);
    $court = Court::factory()->create();

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $match->id)
        ->set('courtId', $court->id)
        ->set('date', now()->addDay()->toDateString())
        ->assertSet('availableSlots', fn (array $slots) => count($slots) > 0);
});

test('confirming a slot schedules the match', function () {
    $player = Player::factory()->create();
    $opponent = Player::factory()->create();
    $match = TournamentMatch::factory()->create(['player1_id' => $player->id, 'player2_id' => $opponent->id]);
    $court = Court::factory()->create();

    session(['player_id' => $player->id]);

    $date = now()->addDay()->toDateString();
    $iso = Carbon::parse($date)->setTime(8, 0)->toIso8601String();

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $match->id)
        ->set('courtId', $court->id)
        ->set('date', $date)
        ->call('confirmSlot', $iso);

    $match->refresh();

    expect($match->status)->toBe(TournamentMatch::STATUS_SCHEDULED)
        ->and($match->court_id)->toBe($court->id)
        ->and($match->scheduled_at->equalTo(Carbon::parse($iso)))->toBeTrue();
});

test('confirming a slot that is no longer available is rejected', function () {
    $player = Player::factory()->create();
    $match = TournamentMatch::factory()->create(['player1_id' => $player->id]);
    $court = Court::factory()->create();

    $conflictingTime = now()->addDay()->setTime(8, 0);
    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => $conflictingTime,
    ]);

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $match->id)
        ->set('courtId', $court->id)
        ->set('date', $conflictingTime->toDateString())
        ->call('confirmSlot', $conflictingTime->toIso8601String())
        ->assertHasErrors('slot');

    expect($match->fresh()->status)->toBe(TournamentMatch::STATUS_PENDING);
});

test('a player cannot open the scheduler for a match they are not part of', function () {
    $player = Player::factory()->create();
    $someoneElsesMatch = TournamentMatch::factory()->create();

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $someoneElsesMatch->id)
        ->assertStatus(403);
});

test('a player cannot schedule a match that is already scheduled', function () {
    $player = Player::factory()->create();
    $match = TournamentMatch::factory()->scheduled()->create([
        'player1_id' => $player->id,
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay(),
    ]);

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $match->id)
        ->assertStatus(403);
});
