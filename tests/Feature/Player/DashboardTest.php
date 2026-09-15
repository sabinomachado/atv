<?php

use App\Livewire\PlayerDashboard;
use App\Models\Category;
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

test('date options start today and span the next 14 days', function () {
    $player = Player::factory()->create();
    TournamentMatch::factory()->create(['player1_id' => $player->id]);

    session(['player_id' => $player->id]);

    $options = Livewire::test(PlayerDashboard::class)->instance()->dateOptions();

    expect($options)->toHaveCount(14)
        ->and($options[0]['value'])->toBe(now()->toDateString())
        ->and($options[13]['value'])->toBe(now()->addDays(13)->toDateString());
});

test('selecting a court and date shows available slots', function () {
    $player = Player::factory()->create();
    $match = TournamentMatch::factory()->create(['player1_id' => $player->id]);
    $court = Court::factory()->create();

    session(['player_id' => $player->id]);

    Livewire::test(PlayerDashboard::class)
        ->call('openScheduler', $match->id)
        ->set('courtId', $court->id)
        ->call('selectDate', now()->addDay()->toDateString())
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
        ->call('selectDate', $date)
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
        ->call('selectDate', $conflictingTime->toDateString())
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

test('opponent options only include other players sharing the chosen category', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    $player = Player::factory()->create();
    $player->categories()->attach($category);

    $sameCategoryOpponent = Player::factory()->create();
    $sameCategoryOpponent->categories()->attach($category);

    $otherCategoryPlayer = Player::factory()->create();
    $otherCategoryPlayer->categories()->attach($otherCategory);

    session(['player_id' => $player->id]);

    $options = Livewire::test(PlayerDashboard::class)
        ->set('newCategoryId', $category->id)
        ->instance()
        ->opponentOptions;

    expect($options->pluck('id'))->toEqual(collect([$sameCategoryOpponent->id]))
        ->and($options->pluck('id'))->not->toContain($player->id)
        ->and($options->pluck('id'))->not->toContain($otherCategoryPlayer->id);
});

test('a player can create and schedule a brand new match with an opponent', function () {
    $category = Category::factory()->create();

    $player = Player::factory()->create();
    $player->categories()->attach($category);

    $opponent = Player::factory()->create();
    $opponent->categories()->attach($category);

    $court = Court::factory()->create();

    session(['player_id' => $player->id]);

    $date = now()->addDay()->toDateString();
    $iso = Carbon::parse($date)->setTime(8, 0)->toIso8601String();

    Livewire::test(PlayerDashboard::class)
        ->call('openNewMatchForm')
        ->set('newCategoryId', $category->id)
        ->set('newOpponentId', $opponent->id)
        ->set('newCourtId', $court->id)
        ->call('selectNewDate', $date)
        ->call('confirmNewMatchSlot', $iso)
        ->assertHasNoErrors();

    $match = TournamentMatch::forPlayer($player->id)->firstOrFail();

    expect($match->player1_id)->toBe($player->id)
        ->and($match->player2_id)->toBe($opponent->id)
        ->and($match->category_id)->toBe($category->id)
        ->and($match->court_id)->toBe($court->id)
        ->and($match->status)->toBe(TournamentMatch::STATUS_SCHEDULED)
        ->and($match->scheduled_at->equalTo(Carbon::parse($iso)))->toBeTrue();
});

test('creating a match with an opponent already matched in that category is rejected', function () {
    $category = Category::factory()->create();

    $player = Player::factory()->create();
    $player->categories()->attach($category);

    $opponent = Player::factory()->create();
    $opponent->categories()->attach($category);

    TournamentMatch::factory()->create([
        'category_id' => $category->id,
        'player1_id' => $player->id,
        'player2_id' => $opponent->id,
    ]);

    $court = Court::factory()->create();

    session(['player_id' => $player->id]);

    $date = now()->addDay()->toDateString();
    $iso = Carbon::parse($date)->setTime(8, 0)->toIso8601String();

    Livewire::test(PlayerDashboard::class)
        ->call('openNewMatchForm')
        ->set('newCategoryId', $category->id)
        ->set('newOpponentId', $opponent->id)
        ->set('newCourtId', $court->id)
        ->call('selectNewDate', $date)
        ->call('confirmNewMatchSlot', $iso)
        ->assertHasErrors('newMatch');

    expect(TournamentMatch::where('category_id', $category->id)->count())->toBe(1);
});
