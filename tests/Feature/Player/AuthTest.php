<?php

use App\Livewire\PlayerLogin;
use App\Models\Player;
use Livewire\Livewire;

test('a player can log in with their registered phone number', function () {
    $player = Player::factory()->create(['phone' => '(48) 99999-8888']);

    Livewire::test(PlayerLogin::class)
        ->set('phone', '48999998888')
        ->call('login')
        ->assertRedirect(route('player.dashboard'));

    expect(session('player_id'))->toBe($player->id);
});

test('the phone field is masked once the user finishes typing a valid length', function () {
    Livewire::test(PlayerLogin::class)
        ->set('phone', '48999998888')
        ->assertSet('phone', '(48) 99999-8888');

    Livewire::test(PlayerLogin::class)
        ->set('phone', '4833334444')
        ->assertSet('phone', '(48) 3333-4444');
});

test('login fails with a helpful error for an unknown phone', function () {
    Livewire::test(PlayerLogin::class)
        ->set('phone', '11900000000')
        ->call('login')
        ->assertHasErrors('phone')
        ->assertNoRedirect();

    expect(session('player_id'))->toBeNull();
});

test('unauthenticated visitors are redirected from the player dashboard to login', function () {
    $this->get('/meus-jogos')->assertRedirect(route('player.login'));
});

test('logging out clears the player session', function () {
    $player = Player::factory()->create();
    session(['player_id' => $player->id]);

    $this->post('/sair')->assertRedirect(route('public.schedule'));

    expect(session('player_id'))->toBeNull();
});
