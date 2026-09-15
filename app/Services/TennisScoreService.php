<?php

namespace App\Services;

class TennisScoreService
{
    /**
     * Minimum games needed to win a super tie-break (3rd set), by a margin of at least 2.
     */
    public const SUPER_TIEBREAK_MIN_POINTS = 10;

    /**
     * A regular set (1st or 2nd): won at 6 with a 2-game margin, at 7-5, or at 7-6 (tiebreak).
     */
    public function isValidRegularSet(int $a, int $b): bool
    {
        if ($a === $b || $a < 0 || $b < 0) {
            return false;
        }

        [$winner, $loser] = $a > $b ? [$a, $b] : [$b, $a];

        if ($winner === 6) {
            return $loser <= 4;
        }

        if ($winner === 7) {
            return in_array($loser, [5, 6], true);
        }

        return false;
    }

    /**
     * The 3rd-set super tie-break: first to 10 (or more), winning by at least 2 points.
     */
    public function isValidSuperTiebreakSet(int $a, int $b): bool
    {
        if ($a === $b || $a < 0 || $b < 0) {
            return false;
        }

        [$winner, $loser] = $a > $b ? [$a, $b] : [$b, $a];

        return $winner >= self::SUPER_TIEBREAK_MIN_POINTS && ($winner - $loser) >= 2;
    }

    /**
     * Validates a best-of-3 (with 3rd-set super tie-break) score.
     *
     * @param  array<int, array{player1: int, player2: int}>  $sets
     * @return string|null An error message in Portuguese, or null if the score is valid.
     */
    public function validationError(array $sets): ?string
    {
        if (count($sets) < 2 || count($sets) > 3) {
            return 'O resultado deve ter 2 ou 3 sets.';
        }

        foreach ([0, 1] as $index) {
            if (! $this->isValidRegularSet($sets[$index]['player1'], $sets[$index]['player2'])) {
                return 'Placar do '.($index + 1).'º set inválido.';
            }
        }

        $firstSetWinner = $this->setWinner($sets[0]);
        $secondSetWinner = $this->setWinner($sets[1]);
        $hasThirdSet = isset($sets[2]);

        if ($firstSetWinner === $secondSetWinner) {
            return $hasThirdSet
                ? 'Não deve haver 3º set: um dos jogadores já venceu os dois primeiros sets.'
                : null;
        }

        if (! $hasThirdSet) {
            return 'É necessário o 3º set (super tie-break), pois os dois primeiros sets ficaram 1 a 1.';
        }

        if (! $this->isValidSuperTiebreakSet($sets[2]['player1'], $sets[2]['player2'])) {
            return 'Placar do super tie-break (3º set) inválido: precisa terminar com 10 pontos ou mais, com pelo menos 2 de diferença.';
        }

        return null;
    }

    /**
     * @param  array<int, array{player1: int, player2: int}>  $sets
     * @return 'player1'|'player2'
     */
    public function matchWinner(array $sets): string
    {
        $player1SetsWon = collect($sets)->filter(fn (array $set) => $this->setWinner($set) === 'player1')->count();

        return $player1SetsWon >= 2 ? 'player1' : 'player2';
    }

    /**
     * @param  array{player1: int, player2: int}  $set
     * @return 'player1'|'player2'
     */
    protected function setWinner(array $set): string
    {
        return $set['player1'] > $set['player2'] ? 'player1' : 'player2';
    }
}
