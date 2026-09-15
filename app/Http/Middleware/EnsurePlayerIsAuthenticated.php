<?php

namespace App\Http\Middleware;

use App\Models\Player;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlayerIsAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $playerId = $request->session()->get('player_id');

        if (! $playerId || ! Player::whereKey($playerId)->exists()) {
            $request->session()->forget('player_id');

            return redirect()->route('player.login');
        }

        return $next($request);
    }
}
