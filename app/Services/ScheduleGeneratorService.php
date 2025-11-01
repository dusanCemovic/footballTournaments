<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use Carbon\Carbon;

final class ScheduleGeneratorService
{
    /**
     * Generate a round-robin schedule using tournament courts, match duration, and start time.
     * Rules implemented:
     * - Round-robin using the circle method; if odd teams, one BYE each round so everyone waits exactly once.
     * - Matches are grouped into rounds. A team appears at most once per round.
     * - Courts are used in parallel; if a round has more matches than courts, we create waves within the round.
     * - Next round starts only after all waves of the previous round finish, plus a 'constant round break'.
     * - Order is randomized by shuffling teams before generating.
     */
    public static function generateForTournament(Tournament $tournament): bool
    {
        $teams = Team::where('tournament_id', $tournament->id)->pluck('id')->shuffle()->values();
        $teamCount = $teams->count();

        // Nothing to schedule if fewer than 2 teams
        if ($teamCount < 2) {
            return true;
        }

        // There is no final match, since this is checked in controller before call
        FootballMatch::where('tournament_id', $tournament->id)
            ->delete();

        // If odd, add a BYE (null) so the circle method works; each team will rest once
        if ($teamCount % 2 === 1) {
            $teams->push(null); // null means BYE
            $teamCount++;
        }

        // Circle method setup - We keep the first team fixed and rotate the rest to produce rounds.
        // eg:
        // first round 1,2,3,4,5,6 so games are  1-6, 2-5, 3-4
        // second round 1,6,2,3,4,5 so games are  1-5, 6-4, 2-3
        // ...

        // For simplicity and to keep home/away random, we don't enforce a strict home-away pattern.
        // Number of rounds is always (teams count - 1) in the circle method (including BYE if present)
        $rounds = $teamCount - 1;
        $half = (int)($teamCount / 2);

        // beginning time of tournament
        $currentRoundStart = Carbon::parse($tournament->start_datetime)->copy();
        // match duration
        $slotMinutes = (int)$tournament->match_duration_minutes;
        // pause between rounds
        $roundBreakMinutes = 20; // showcase break between rounds
        // number of courts
        $courts = (int)$tournament->courts;

        // Prepare rotating array
        $arr = $teams->all();

        for ($round = 0; $round < $rounds; $round++) {
            // Build pairings for this round
            $pairings = [];
            for ($i = 0; $i < $half; $i++) {
                $home = $arr[$i];
                $away = $arr[$teamCount - 1 - $i];
                if ($home === null || $away === null) {
                    // BYE — skip creating a match
                    continue;
                }
                // Randomize home/away a bit
                if (random_int(0, 1) === 1) {
                    [$home, $away] = [$away, $home];
                }
                $pairings[] = [$home, $away];
            }

            // Assign pairings to courts in waves
            $totalMatches = count($pairings);
            if ($totalMatches === 0) {
                // No matches this round (possible only with very small or edge cases), still advance time by break
                $currentRoundStart = $currentRoundStart->copy()->addMinutes($roundBreakMinutes);
            } else {
                // If we have more matches then courts, we are plating in waves
                $waves = (int)ceil($totalMatches / $courts);
                $matchIndex = 0;
                for ($wave = 0; $wave < $waves; $wave++) {
                    $waveStart = $currentRoundStart->copy()->addMinutes($wave * $slotMinutes);
                    $waveEnd = $waveStart->copy()->addMinutes($slotMinutes);

                    for ($court = 1; $court <= $courts && $matchIndex < $totalMatches; $court++) {
                        [$homeId, $awayId] = $pairings[$matchIndex];
                        FootballMatch::create([
                            'tournament_id' => $tournament->id,
                            'home_team_id' => $homeId,
                            'away_team_id' => $awayId,
                            'court_number' => $court,
                            'start_datetime' => $waveStart,
                            'end_datetime' => $waveEnd,
                            'home_goals' => null,
                            'away_goals' => null,
                            'is_final' => false,
                            'unfinalize_count' => 0,
                        ]);
                        $matchIndex++;
                    }
                }
                // Next round starts after all waves and a break
                $currentRoundStart = $currentRoundStart->copy()->addMinutes($waves * $slotMinutes + $roundBreakMinutes);
            }

            // Rotate teams for next round (circle method)
            // Keep first element fixed; rotate the rest to the right by 1
            $fixed = $arr[0];
            $rotating = array_slice($arr, 1);
            array_unshift($rotating, array_pop($rotating));
            $arr = array_merge([$fixed], $rotating);
        }

        return true;
    }
}
