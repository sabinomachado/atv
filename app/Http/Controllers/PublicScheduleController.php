<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Court;
use App\Models\TournamentMatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $courts = Court::active()->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        $matches = TournamentMatch::query()
            ->whereNotNull('scheduled_at')
            ->whereIn('status', [TournamentMatch::STATUS_SCHEDULED, TournamentMatch::STATUS_COMPLETED])
            ->when($request->filled('court_id'), fn ($query) => $query->where('court_id', $request->integer('court_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->with(['category', 'player1', 'player2', 'court'])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn (TournamentMatch $match) => $match->scheduled_at->toDateString());

        return view('public.schedule', compact('courts', 'categories', 'matches'));
    }
}
