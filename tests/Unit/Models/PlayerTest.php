<?php

use App\Models\Player;

test('phone is normalized to digits only when set', function () {
    $player = Player::factory()->make(['phone' => '(48) 99999-8888']);

    expect($player->phone)->toBe('48999998888');
});

test('mask phone formats an 11-digit mobile number', function () {
    expect(Player::maskPhone('48999998888'))->toBe('(48) 99999-8888');
});

test('mask phone formats a 10-digit landline number', function () {
    expect(Player::maskPhone('4833334444'))->toBe('(48) 3333-4444');
});

test('mask phone returns null for a blank phone', function () {
    expect(Player::maskPhone(null))->toBeNull()
        ->and(Player::maskPhone(''))->toBeNull();
});
