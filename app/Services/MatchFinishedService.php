<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Tournament;

/**
 * This service is used for showcase as helper.
 */
final class MatchFinishedService
{
    public static function run(Tournament $tournament, bool $finishAll = false): void
    {
        $matches = FootballMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereNotNull('start_datetime') // only scheduled games
            ->orderBy('start_datetime')
            ->orderBy('id') // stable tie-breaker
            ->get();

        if ($matches->isEmpty()) {
            return; // No schedule yet
        }

        // in case to finish all
        if($finishAll) {
            $n = $matches->count();
        } else {
            // Pick a random count N in [0, total scheduled matches]
            $n = random_int(0, $matches->count());
            if ($n === 0) {
                return; // finalize none
            }
        }

        $toFinalize = $matches->take($n);

        foreach ($toFinalize as $match) {
            if ($match->is_final) {
                continue; // don't overwrite finals
            }

            // Generate random plausible football score (0-5 goals each)
            $home = random_int(0, 5);
            $away = random_int(0, 5);

            $match->home_goals = $home;
            $match->away_goals = $away;
            $match->is_final = true;
            $match->save();
        }
    }
}
