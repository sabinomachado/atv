<?php

use App\Services\TennisScoreService;

beforeEach(function () {
    $this->service = new TennisScoreService;
});

test('regular set validity', function (int $a, int $b, bool $valid) {
    expect($this->service->isValidRegularSet($a, $b))->toBe($valid);
})->with([
    'straight 6-0' => [6, 0, true],
    'straight 6-4' => [6, 4, true],
    '6-5 is not a valid final score' => [6, 5, false],
    '7-5' => [7, 5, true],
    '7-6 tiebreak' => [7, 6, true],
    '7-4 is not valid' => [7, 4, false],
    '8-6 is not valid' => [8, 6, false],
    'tied 6-6 is not a final score' => [6, 6, false],
    'negative score is invalid' => [-1, 6, false],
]);

test('super tie-break validity', function (int $a, int $b, bool $valid) {
    expect($this->service->isValidSuperTiebreakSet($a, $b))->toBe($valid);
})->with([
    '10-8' => [10, 8, true],
    '10-9 is only a 1-point margin' => [10, 9, false],
    '12-10 extended tiebreak' => [12, 10, true],
    '9-7 never reached 10' => [9, 7, false],
    'tied 10-10' => [10, 10, false],
]);

test('a straight-sets match needs no 3rd set', function () {
    $sets = [
        ['player1' => 6, 'player2' => 3],
        ['player1' => 6, 'player2' => 4],
    ];

    expect($this->service->validationError($sets))->toBeNull()
        ->and($this->service->matchWinner($sets))->toBe('player1');
});

test('a split match requires a super tie-break 3rd set', function () {
    $sets = [
        ['player1' => 7, 'player2' => 6],
        ['player1' => 4, 'player2' => 6],
    ];

    expect($this->service->validationError($sets))
        ->toBe('É necessário o 3º set (super tie-break), pois os dois primeiros sets ficaram 1 a 1.');
});

test('a valid 3-set match with a super tie-break is accepted and resolves the winner', function () {
    $sets = [
        ['player1' => 7, 'player2' => 6],
        ['player1' => 6, 'player2' => 7],
        ['player1' => 10, 'player2' => 8],
    ];

    expect($this->service->validationError($sets))->toBeNull()
        ->and($this->service->matchWinner($sets))->toBe('player1');
});

test('a 3rd set is rejected when one player already won the first two', function () {
    $sets = [
        ['player1' => 6, 'player2' => 2],
        ['player1' => 6, 'player2' => 3],
        ['player1' => 10, 'player2' => 8],
    ];

    expect($this->service->validationError($sets))
        ->toBe('Não deve haver 3º set: um dos jogadores já venceu os dois primeiros sets.');
});

test('an invalid 1st or 2nd set score is rejected', function () {
    $sets = [
        ['player1' => 6, 'player2' => 5],
        ['player1' => 6, 'player2' => 3],
    ];

    expect($this->service->validationError($sets))->toBe('Placar do 1º set inválido.');
});

test('an invalid super tie-break score is rejected', function () {
    $sets = [
        ['player1' => 7, 'player2' => 6],
        ['player1' => 4, 'player2' => 6],
        ['player1' => 10, 'player2' => 9],
    ];

    expect($this->service->validationError($sets))
        ->toBe('Placar do super tie-break (3º set) inválido: precisa terminar com 10 pontos ou mais, com pelo menos 2 de diferença.');
});

test('fewer than 2 sets or more than 3 sets is rejected', function () {
    expect($this->service->validationError([
        ['player1' => 6, 'player2' => 3],
    ]))->toBe('O resultado deve ter 2 ou 3 sets.');
});

test('match winner is player2 when they win the majority of sets', function () {
    $sets = [
        ['player1' => 4, 'player2' => 6],
        ['player1' => 3, 'player2' => 6],
    ];

    expect($this->service->matchWinner($sets))->toBe('player2');
});
