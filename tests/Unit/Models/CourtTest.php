<?php

use App\Models\Court;

test('active scope only returns active courts', function () {
    Court::factory()->create(['name' => 'Ativa 1', 'is_active' => true]);
    Court::factory()->create(['name' => 'Ativa 2', 'is_active' => true]);
    Court::factory()->inactive()->create(['name' => 'Inativa']);

    $names = Court::active()->pluck('name')->sort()->values();

    expect($names)->toEqual(collect(['Ativa 1', 'Ativa 2']));
});

test('courts are active by default', function () {
    $court = Court::factory()->create();

    expect($court->is_active)->toBeTrue();
});
