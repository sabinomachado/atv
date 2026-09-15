<?php

use App\Http\Controllers\PublicScheduleController;
use App\Livewire\PlayerDashboard;
use App\Livewire\PlayerLogin;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicScheduleController::class, 'index'])->name('public.schedule');

Route::get('/entrar', PlayerLogin::class)->name('player.login');

Route::post('/sair', function () {
    session()->forget('player_id');

    return redirect()->route('public.schedule');
})->name('player.logout');

Route::middleware('player.auth')->group(function () {
    Route::get('/meus-jogos', PlayerDashboard::class)->name('player.dashboard');
});
