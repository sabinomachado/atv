<?php

use App\Models\BlockedSlot;
use App\Models\Court;
use App\Models\TournamentMatch;
use App\Services\MatchAvailabilityService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new MatchAvailabilityService;
});

test('slot is available when court is free', function () {
    $court = Court::factory()->create();

    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 10:00:00')))->toBeTrue();
});

test('slot exactly on top of another match is unavailable', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    $dateTime = Carbon::parse('2026-01-05 10:00:00');

    expect($this->service->isAvailable($court, $dateTime))->toBeFalse()
        ->and($this->service->unavailabilityReason($court, $dateTime))
        ->toBe(MatchAvailabilityService::REASON_MATCH_CONFLICT);
});

test('slot inside the 90 minute window before an existing match is unavailable', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    // Only 89 minutes before the existing match.
    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 08:31:00')))->toBeFalse();
});

test('slot inside the 90 minute window after an existing match is unavailable', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    // Only 89 minutes after the existing match.
    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 11:29:00')))->toBeFalse();
});

test('slot exactly 90 minutes away from an existing match is available', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 08:30:00')))->toBeTrue()
        ->and($this->service->isAvailable($court, Carbon::parse('2026-01-05 11:30:00')))->toBeTrue();
});

test('conflict check is scoped to the same court', function () {
    $courtA = Court::factory()->create();
    $courtB = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $courtA->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    expect($this->service->isAvailable($courtB, Carbon::parse('2026-01-05 10:00:00')))->toBeTrue();
});

test('cancelled matches do not block the slot', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->cancelled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 10:00:00')))->toBeTrue();
});

test('editing a match ignores its own conflict', function () {
    $court = Court::factory()->create();

    $match = TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    // Re-validating the same match at its own time must not conflict with itself.
    $available = $this->service->isAvailable(
        $court,
        Carbon::parse('2026-01-05 10:00:00'),
        ignoreMatchId: $match->id
    );

    expect($available)->toBeTrue();
});

test('slot inside a blocked period is unavailable', function () {
    $court = Court::factory()->create();

    BlockedSlot::factory()->create([
        'court_id' => $court->id,
        'start_datetime' => Carbon::parse('2026-01-05 09:00:00'),
        'end_datetime' => Carbon::parse('2026-01-05 12:00:00'),
    ]);

    $dateTime = Carbon::parse('2026-01-05 10:00:00');

    expect($this->service->isAvailable($court, $dateTime))->toBeFalse()
        ->and($this->service->unavailabilityReason($court, $dateTime))
        ->toBe(MatchAvailabilityService::REASON_BLOCKED_SLOT);
});

test('slot at the exact end of a blocked period is available', function () {
    $court = Court::factory()->create();

    BlockedSlot::factory()->create([
        'court_id' => $court->id,
        'start_datetime' => Carbon::parse('2026-01-05 09:00:00'),
        'end_datetime' => Carbon::parse('2026-01-05 12:00:00'),
    ]);

    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 12:00:00')))->toBeTrue();
});

test('training court is unavailable on weekdays', function () {
    $trainingCourt = Court::factory()->trainingCourt()->create();

    // 2026-01-05 is a Monday.
    $dateTime = Carbon::parse('2026-01-05 10:00:00');

    expect($this->service->isAvailable($trainingCourt, $dateTime))->toBeFalse()
        ->and($this->service->unavailabilityReason($trainingCourt, $dateTime))
        ->toBe(MatchAvailabilityService::REASON_COURT_UNAVAILABLE_ON_DAY);
});

test('training court is available on saturday and sunday', function () {
    $trainingCourt = Court::factory()->trainingCourt()->create();

    // 2026-01-03 is a Saturday, 2026-01-04 is a Sunday.
    expect($this->service->isAvailable($trainingCourt, Carbon::parse('2026-01-03 10:00:00')))->toBeTrue()
        ->and($this->service->isAvailable($trainingCourt, Carbon::parse('2026-01-04 10:00:00')))->toBeTrue();
});

test('regular courts are available every day of the week', function () {
    $court = Court::factory()->create();

    // 2026-01-05 is a Monday.
    expect($this->service->isAvailable($court, Carbon::parse('2026-01-05 10:00:00')))->toBeTrue();
});

test('available slots from filters out invalid candidates', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 10:00:00'),
    ]);

    $candidates = [
        Carbon::parse('2026-01-05 09:00:00'), // 60 min before -> conflict
        Carbon::parse('2026-01-05 10:00:00'), // exact conflict
        Carbon::parse('2026-01-05 11:30:00'), // exactly 90 min after -> valid
        Carbon::parse('2026-01-05 13:00:00'), // clearly free -> valid
    ];

    $available = $this->service->availableSlotsFrom($court, $candidates);

    expect($available)->toHaveCount(2)
        ->and($available[0]->equalTo(Carbon::parse('2026-01-05 11:30:00')))->toBeTrue()
        ->and($available[1]->equalTo(Carbon::parse('2026-01-05 13:00:00')))->toBeTrue();
});

test('slots for date generates slots across the daily window every 90 minutes', function () {
    $court = Court::factory()->create();

    $slots = $this->service->slotsForDate($court, Carbon::parse('2026-01-05'));

    expect($slots)->toHaveCount(9)
        ->and($slots[0]->format('H:i'))->toBe('08:00')
        ->and($slots[1]->format('H:i'))->toBe('09:30')
        ->and(last($slots)->format('H:i'))->toBe('20:00');
});

test('slots for date excludes a slot taken by an existing match', function () {
    $court = Court::factory()->create();

    TournamentMatch::factory()->scheduled()->create([
        'court_id' => $court->id,
        'scheduled_at' => Carbon::parse('2026-01-05 11:00:00'),
    ]);

    $slots = $this->service->slotsForDate($court, Carbon::parse('2026-01-05'));

    expect($slots)->toHaveCount(8)
        ->and(collect($slots)->contains(fn ($slot) => $slot->format('H:i') === '11:00'))->toBeFalse();
});

test('slots for date is empty for a training court on a weekday', function () {
    $trainingCourt = Court::factory()->trainingCourt()->create();

    // 2026-01-05 is a Monday.
    $slots = $this->service->slotsForDate($trainingCourt, Carbon::parse('2026-01-05'));

    expect($slots)->toBeEmpty();
});
