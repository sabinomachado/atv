<?php

use App\Models\TournamentMatch;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('admin panel dashboard loads', function () {
    $this->get('/admin')->assertSuccessful();
});

test('court resource pages load', function () {
    $this->get('/admin/courts')->assertSuccessful();
    $this->get('/admin/courts/create')->assertSuccessful();
});

test('category resource pages load', function () {
    $this->get('/admin/categories')->assertSuccessful();
    $this->get('/admin/categories/create')->assertSuccessful();
});

test('player resource pages load', function () {
    $this->get('/admin/players')->assertSuccessful();
    $this->get('/admin/players/create')->assertSuccessful();
});

test('tournament match resource pages load', function () {
    $this->get('/admin/tournament-matches')->assertSuccessful();
    $this->get('/admin/tournament-matches/create')->assertSuccessful();

    $match = TournamentMatch::factory()->create();
    $this->get("/admin/tournament-matches/{$match->id}/edit")->assertSuccessful();
});

test('blocked slot resource pages load', function () {
    $this->get('/admin/blocked-slots')->assertSuccessful();
    $this->get('/admin/blocked-slots/create')->assertSuccessful();
});
