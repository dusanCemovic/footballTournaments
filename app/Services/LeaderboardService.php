<?php

namespace App\Services;

use App\Models\Tournament;

class LeaderboardService
{
    /**
     * Calculate leaderboard for a tournament.
     */
    public function calculateForTournament(Tournament $tournament): array
    {
        // TODO: Implement leaderboard calculation (points, H2H, GD, GF, avg earliest start time)
        return [];
    }
}
