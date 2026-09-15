<?php

namespace App\Services;

use App\Models\Court;
use App\Models\TournamentMatch;
use Carbon\CarbonInterface;

class MatchAvailabilityService
{
    /**
     * Minimum gap, in minutes, required between the start times of two
     * matches on the same court.
     */
    public const MIN_INTERVAL_MINUTES = 90;

    public const REASON_COURT_UNAVAILABLE_ON_DAY = 'court_unavailable_on_day';

    public const REASON_BLOCKED_SLOT = 'blocked_slot';

    public const REASON_MATCH_CONFLICT = 'match_conflict';

    /**
     * Daily scheduling window used to generate candidate slots for the player
     * self-scheduling screen. Courts don't have their own opening hours yet,
     * so this is a single club-wide assumption.
     */
    public const DAILY_START_HOUR = 8;

    public const DAILY_END_HOUR = 21;

    /**
     * Whether a match can be scheduled on the given court at the given date/time.
     *
     * $ignoreMatchId excludes a match from the conflict check, which is needed
     * when validating an admin edit to a match that is already scheduled.
     */
    public function isAvailable(Court $court, CarbonInterface $dateTime, ?int $ignoreMatchId = null): bool
    {
        return $this->unavailabilityReason($court, $dateTime, $ignoreMatchId) === null;
    }

    /**
     * Returns why a slot is unavailable, or null if it is valid.
     */
    public function unavailabilityReason(Court $court, CarbonInterface $dateTime, ?int $ignoreMatchId = null): ?string
    {
        if (! $court->isAvailableOnDay($dateTime->format('l'))) {
            return self::REASON_COURT_UNAVAILABLE_ON_DAY;
        }

        if ($this->isBlocked($court, $dateTime)) {
            return self::REASON_BLOCKED_SLOT;
        }

        if ($this->conflictsWithExistingMatch($court, $dateTime, $ignoreMatchId)) {
            return self::REASON_MATCH_CONFLICT;
        }

        return null;
    }

    /**
     * Filters a list of candidate date/times down to the ones that are valid,
     * so the same rules drive both the player's slot picker and admin screens.
     *
     * @param  CarbonInterface[]  $candidateDateTimes
     * @return CarbonInterface[]
     */
    public function availableSlotsFrom(Court $court, array $candidateDateTimes, ?int $ignoreMatchId = null): array
    {
        return array_values(array_filter(
            $candidateDateTimes,
            fn (CarbonInterface $dateTime) => $this->isAvailable($court, $dateTime, $ignoreMatchId)
        ));
    }

    /**
     * All valid slot start times for a court on a given day, spaced by the
     * minimum interval, within the club's daily scheduling window.
     *
     * @return CarbonInterface[]
     */
    public function slotsForDate(Court $court, CarbonInterface $date, ?int $ignoreMatchId = null): array
    {
        $slot = $date->copy()->startOfDay()->addHours(self::DAILY_START_HOUR);
        $end = $date->copy()->startOfDay()->addHours(self::DAILY_END_HOUR);

        $candidates = [];

        while ($slot->lte($end)) {
            $candidates[] = $slot->copy();
            $slot = $slot->addMinutes(self::MIN_INTERVAL_MINUTES);
        }

        return $this->availableSlotsFrom($court, $candidates, $ignoreMatchId);
    }

    protected function isBlocked(Court $court, CarbonInterface $dateTime): bool
    {
        return $court->blockedSlots()
            ->where('start_datetime', '<=', $dateTime)
            ->where('end_datetime', '>', $dateTime)
            ->exists();
    }

    protected function conflictsWithExistingMatch(Court $court, CarbonInterface $dateTime, ?int $ignoreMatchId): bool
    {
        return $court->matches()
            ->whereNotNull('scheduled_at')
            // A cancelled match no longer occupies its slot on the court.
            ->where('status', '!=', TournamentMatch::STATUS_CANCELLED)
            ->when($ignoreMatchId, fn ($query) => $query->whereKeyNot($ignoreMatchId))
            ->get()
            // Carbon's diffInMinutes returns a signed value, so the gap must be made absolute here.
            ->contains(fn (TournamentMatch $match) => abs($match->scheduled_at->diffInMinutes($dateTime)) < self::MIN_INTERVAL_MINUTES);
    }
}
