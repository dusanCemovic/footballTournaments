<?php

namespace App\Services;

use App\Models\Tournament;

class ScheduleGeneratorService
{
    /**
     * Generate a round-robin schedule using tournament courts, match duration, and start time.
     * After matches are finished we can have pause 1hour just for showcase, but we can use this "waiting" as const here in method. Then next round is schedule.
     * Make sure that we can have odd number of teams. Each round is with different teams.
     * When generating, order is random.
     */
    public function generateForTournament(Tournament $tournament): void
    {
        // TODO: Implement schedule generation according to README rules.
    }
}
