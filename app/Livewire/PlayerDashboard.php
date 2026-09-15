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

        $slots = app(MatchAvailabilityService::class)->slotsForDate(
            $court,
            Carbon::parse($this->date),
            $this->activeMatchId,
        );

        $this->availableSlots = collect($slots)->map(fn (Carbon $slot) => $slot->toIso8601String())->all();
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
