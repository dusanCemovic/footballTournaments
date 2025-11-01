<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\LeaderboardService;

class LeaderboardController extends Controller
{
    public function index(Tournament $tournament, LeaderboardService $service)
    {
        // hit calculation for standings
        $leaderboard = $service->calculateForTournament($tournament);

        return response()->json([
            'tournament_id' => $tournament->id,
            'leaderboard' => $leaderboard,
            'message' => 'Leaderboard calculation for standings'
        ]);
    }
}
