<?php

use App\Filament\Resources\TournamentMatchResource\Pages\CreateTournamentMatch;
use App\Filament\Resources\TournamentMatchResource\Pages\ListTournamentMatches;
use App\Models\Category;
use App\Models\Court;
use App\Models\Player;
use App\Models\TournamentMatch;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admin can register a straight-sets match result', function () {
    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::test(ListTournamentMatches::class)
        ->callTableAction('registerResult', $match, data: [
            'set1_player1' => 6,
            'set1_player2' => 3,
            'set2_player1' => 6,
            'set2_player2' => 4,
            'set3_player1' => null,
            'set3_player2' => null,
        ])
        ->assertHasNoTableActionErrors();

    $match->refresh();

    expect($match->status)->toBe(TournamentMatch::STATUS_COMPLETED)
        ->and($match->winner_player_id)->toBe($match->player1_id)
        ->and($match->formattedScore())->toBe('6-3, 6-4');
});

test('admin can register a 3-set match result with a super tie-break', function () {
    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::test(ListTournamentMatches::class)
        ->callTableAction('registerResult', $match, data: [
            'set1_player1' => 7,
            'set1_player2' => 6,
            'set2_player1' => 6,
            'set2_player2' => 7,
            'set3_player1' => 10,
            'set3_player2' => 8,
        ])
        ->assertHasNoTableActionErrors();

    $match->refresh();

    expect($match->status)->toBe(TournamentMatch::STATUS_COMPLETED)
        ->and($match->winner_player_id)->toBe($match->player1_id)
        ->and($match->formattedScore())->toBe('7-6, 6-7, 10-8');
});

test('a score missing the required 3rd set is rejected', function () {
    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::test(ListTournamentMatches::class)
        ->callTableAction('registerResult', $match, data: [
            'set1_player1' => 7,
            'set1_player2' => 6,
            'set2_player1' => 4,
            'set2_player2' => 6,
            'set3_player1' => null,
            'set3_player2' => null,
        ])
        ->assertHasTableActionErrors();

    expect($match->fresh()->status)->toBe(TournamentMatch::STATUS_SCHEDULED);
});

test('a half-filled 3rd set is rejected', function () {
    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::test(ListTournamentMatches::class)
        ->callTableAction('registerResult', $match, data: [
            'set1_player1' => 7,
            'set1_player2' => 6,
            'set2_player1' => 4,
            'set2_player2' => 6,
            'set3_player1' => 10,
            'set3_player2' => null,
        ])
        ->assertHasTableActionErrors();

    expect($match->fresh()->status)->toBe(TournamentMatch::STATUS_SCHEDULED);
});

test('the register result action is not available for a pending match', function () {
    $match = TournamentMatch::factory()->create();

    Livewire::test(ListTournamentMatches::class)
        ->assertTableActionHidden('registerResult', $match);
});

test('court and date are required when saving a match as scheduled or completed', function () {
    $category = Category::factory()->create();
    $player1 = Player::factory()->create();
    $player2 = Player::factory()->create();

    Livewire::test(CreateTournamentMatch::class)
        ->fillForm([
            'category_id' => $category->id,
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'status' => TournamentMatch::STATUS_SCHEDULED,
            'court_id' => null,
            'scheduled_at' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['court_id', 'scheduled_at']);

    expect(TournamentMatch::count())->toBe(0);
});

test('court and date stay optional for a pending match', function () {
    $category = Category::factory()->create();
    $player1 = Player::factory()->create();
    $player2 = Player::factory()->create();

    Livewire::test(CreateTournamentMatch::class)
        ->fillForm([
            'category_id' => $category->id,
            'player1_id' => $player1->id,
            'player2_id' => $player2->id,
            'status' => TournamentMatch::STATUS_PENDING,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TournamentMatch::where('status', TournamentMatch::STATUS_PENDING)->exists())->toBeTrue();
});
