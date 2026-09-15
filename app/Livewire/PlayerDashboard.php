<?php

namespace App\Livewire;

use App\Models\Court;
use App\Models\Player;
use App\Models\TournamentMatch;
use App\Services\MatchAvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PlayerDashboard extends Component
{
    public Player $player;

    public ?int $activeMatchId = null;

    public ?int $courtId = null;

    public string $date = '';

    /** @var string[] ISO-8601 datetimes available for the active match/court/date. */
    public array $availableSlots = [];

    public bool $creatingMatch = false;

    public ?int $newCategoryId = null;

    public ?int $newOpponentId = null;

    public ?int $newCourtId = null;

    public string $newDate = '';

    /** @var string[] ISO-8601 datetimes available for the new match's court/date. */
    public array $newAvailableSlots = [];

    public ?string $successMessage = null;

    public function mount(): void
    {
        $this->player = Player::findOrFail(session('player_id'));
        $this->date = now()->addDay()->toDateString();
    }

    #[Computed]
    public function pendingMatches(): Collection
    {
        return TournamentMatch::query()
            ->forPlayer($this->player->id)
            ->where('status', TournamentMatch::STATUS_PENDING)
            ->with(['category', 'player1', 'player2'])
            ->get();
    }

    #[Computed]
    public function upcomingMatches(): Collection
    {
        return TournamentMatch::query()
            ->forPlayer($this->player->id)
            ->where('status', TournamentMatch::STATUS_SCHEDULED)
            ->with(['category', 'player1', 'player2', 'court'])
            ->orderBy('scheduled_at')
            ->get();
    }

    #[Computed]
    public function courts(): Collection
    {
        return Court::active()->orderBy('name')->get();
    }

    #[Computed]
    public function myCategories(): Collection
    {
        return $this->player->categories;
    }

    /**
     * Other players who share the chosen category, to pick as an opponent.
     */
    #[Computed]
    public function opponentOptions(): Collection
    {
        if (! $this->newCategoryId) {
            return new Collection;
        }

        return Player::query()
            ->whereKeyNot($this->player->id)
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $this->newCategoryId))
            ->orderBy('name')
            ->get();
    }

    /**
     * The next two weeks, rendered as tappable day chips instead of a native
     * date input (friendlier on mobile, and consistent across browsers).
     *
     * @return array<int, array{value: string, weekday: string, day: string, month: string}>
     */
    #[Computed]
    public function dateOptions(): array
    {
        return collect(range(0, 13))
            ->map(function (int $daysFromNow) {
                $date = now()->addDays($daysFromNow);

                return [
                    'value' => $date->toDateString(),
                    'weekday' => $date->translatedFormat('D'),
                    'day' => $date->format('d'),
                    'month' => $date->translatedFormat('M'),
                ];
            })
            ->all();
    }

    public function selectDate(string $date): void
    {
        $this->date = $date;
        $this->refreshSlots();
    }

    public function openScheduler(int $matchId): void
    {
        $this->authorizePendingMatch($matchId);

        $this->closeNewMatchForm();
        $this->activeMatchId = $matchId;
        $this->courtId = null;
        $this->availableSlots = [];
        $this->successMessage = null;
        $this->resetErrorBag();
    }

    public function closeScheduler(): void
    {
        $this->reset(['activeMatchId', 'courtId', 'availableSlots']);
    }

    public function updatedCourtId(): void
    {
        $this->refreshSlots();
    }

    protected function refreshSlots(): void
    {
        $this->resetErrorBag();
        $this->availableSlots = [];

        if (! $this->activeMatchId || ! $this->courtId || blank($this->date)) {
            return;
        }

        $court = Court::find($this->courtId);

        if (! $court) {
            return;
        }

        $this->availableSlots = $this->computeSlots($court, $this->date, $this->activeMatchId);
    }

    public function confirmSlot(string $iso): void
    {
        $match = $this->authorizePendingMatch($this->activeMatchId);

        if (! $this->courtId) {
            return;
        }

        $court = Court::findOrFail($this->courtId);
        $dateTime = Carbon::parse($iso);

        // Re-validate against the live schedule in case another player took the slot
        // between rendering the buttons and this click.
        $reason = app(MatchAvailabilityService::class)->unavailabilityReason($court, $dateTime, $match->id);

        if ($reason !== null) {
            // refreshSlots() resets the error bag, so it must run before addError().
            $this->refreshSlots();
            $this->addError('slot', 'Esse horário não está mais disponível. Escolha outro.');

            return;
        }

        $match->update([
            'court_id' => $court->id,
            'scheduled_at' => $dateTime,
            'status' => TournamentMatch::STATUS_SCHEDULED,
        ]);

        $this->successMessage = 'Jogo marcado para '.$dateTime->translatedFormat('d/m/Y \à\s H:i').' na quadra '.$court->name.'.';

        unset($this->pendingMatches, $this->upcomingMatches);
        $this->reset(['activeMatchId', 'courtId', 'availableSlots']);
    }

    public function openNewMatchForm(): void
    {
        $this->closeScheduler();
        $this->creatingMatch = true;
        $this->newCategoryId = null;
        $this->newOpponentId = null;
        $this->newCourtId = null;
        $this->newDate = now()->addDay()->toDateString();
        $this->newAvailableSlots = [];
        $this->successMessage = null;
        $this->resetErrorBag();
    }

    public function closeNewMatchForm(): void
    {
        $this->reset(['creatingMatch', 'newCategoryId', 'newOpponentId', 'newCourtId', 'newAvailableSlots']);
    }

    public function updatedNewCategoryId(): void
    {
        $this->newOpponentId = null;
        $this->newAvailableSlots = [];
        $this->resetErrorBag();
    }

    public function updatedNewOpponentId(): void
    {
        $this->resetErrorBag();
    }

    public function updatedNewCourtId(): void
    {
        $this->refreshNewMatchSlots();
    }

    public function selectNewDate(string $date): void
    {
        $this->newDate = $date;
        $this->refreshNewMatchSlots();
    }

    protected function refreshNewMatchSlots(): void
    {
        $this->resetErrorBag();
        $this->newAvailableSlots = [];

        if (! $this->newCourtId || blank($this->newDate)) {
            return;
        }

        $court = Court::find($this->newCourtId);

        if (! $court) {
            return;
        }

        $this->newAvailableSlots = $this->computeSlots($court, $this->newDate);
    }

    public function confirmNewMatchSlot(string $iso): void
    {
        if (! $this->newCategoryId || ! $this->newOpponentId || ! $this->newCourtId) {
            return;
        }

        $opponent = $this->opponentOptions->firstWhere('id', $this->newOpponentId);

        abort_unless($opponent, 403);

        if ($this->opponentAlreadyMatched($opponent->id)) {
            $this->addError('newMatch', 'Vocês já têm um confronto nessa categoria.');

            return;
        }

        $court = Court::findOrFail($this->newCourtId);
        $dateTime = Carbon::parse($iso);

        // Re-validate against the live schedule in case someone else took the slot
        // between rendering the buttons and this click.
        $reason = app(MatchAvailabilityService::class)->unavailabilityReason($court, $dateTime);

        if ($reason !== null) {
            $this->refreshNewMatchSlots();
            $this->addError('newMatch', 'Esse horário não está mais disponível. Escolha outro.');

            return;
        }

        TournamentMatch::create([
            'category_id' => $this->newCategoryId,
            'player1_id' => $this->player->id,
            'player2_id' => $opponent->id,
            'court_id' => $court->id,
            'scheduled_at' => $dateTime,
            'duration_minutes' => 90,
            'status' => TournamentMatch::STATUS_SCHEDULED,
        ]);

        $this->successMessage = 'Jogo marcado com '.$opponent->name.' para '.$dateTime->translatedFormat('d/m/Y \à\s H:i').' na quadra '.$court->name.'.';

        unset($this->upcomingMatches);
        $this->closeNewMatchForm();
    }

    protected function opponentAlreadyMatched(int $opponentId): bool
    {
        return TournamentMatch::where('category_id', $this->newCategoryId)
            ->forPlayer($this->player->id)
            ->where(fn ($query) => $query->where('player1_id', $opponentId)->orWhere('player2_id', $opponentId))
            ->whereIn('status', [TournamentMatch::STATUS_PENDING, TournamentMatch::STATUS_SCHEDULED])
            ->exists();
    }

    /**
     * @return string[]
     */
    protected function computeSlots(Court $court, string $date, ?int $ignoreMatchId = null): array
    {
        $slots = app(MatchAvailabilityService::class)->slotsForDate($court, Carbon::parse($date), $ignoreMatchId);

        return collect($slots)->map(fn (Carbon $slot) => $slot->toIso8601String())->all();
    }

    protected function authorizePendingMatch(?int $matchId): TournamentMatch
    {
        $match = TournamentMatch::findOrFail($matchId);

        abort_unless($match->involvesPlayer($this->player->id), 403);
        abort_unless($match->status === TournamentMatch::STATUS_PENDING, 403);

        return $match;
    }

    public function render()
    {
        return view('livewire.player-dashboard');
    }
}
