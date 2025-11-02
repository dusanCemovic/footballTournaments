<?php

namespace App\Services;

use App\Models\Tournament;

final class LeaderboardService
{
    /**
     * Calculate leaderboard for a tournament.
     *
     * Ranking rules (README):
     * - Points: Win=3, Draw=1, Loss=0
     * - Tie-breakers (in order):
     *   1) Head-to-head results between tied teams (points only)
     *   2) Goal difference (GF − GA)
     *   3) Goals scored (GF)
     *   4) Average earliest start time of matches (earlier ranks higher)
     *
     * Returns an array of rows with: position, team_id, team_name, played, wins, draws, losses,
     * gf, ga, gd, points.
     */
    public static function calculateForTournament(Tournament $tournament): array
    {
        $teams = $tournament->teams()->orderBy('name')->get();

        // Initialize stats for every team so teams with 0 matches still appear
        $stats = [];
        foreach ($teams as $team) {
            $stats[$team->id] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'played' => 0,
                'wins' => 0,
                'draws' => 0,
                'losses' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0, // computed at the end
                'points' => 0,
                'avg_start_ts' => null, // average timestamp (int) of final matches, // computed at the end
                'start_ts_sum' => 0,
                'start_ts_count' => 0,
                // For head-to-head: points earned vs specific opponent
                'h2h' => [], // [opponent_id => points]
            ];
        }

        // Use only final matches
        $finalMatches = $tournament->matches()
            ->where('is_final', true)
            ->get();

        // Aggregate per team + prepare h2h points maps
        foreach ($finalMatches as $match) {
            $homeId = $match->home_team_id;
            $awayId = $match->away_team_id;
            // Ensure both teams belong to this tournament and exist in $stats
            if (!isset($stats[$homeId]) || !isset($stats[$awayId])) {
                continue;
            }
            $homeGoals = (int)$match->home_goals;
            $awayGoals = (int)$match->away_goals;

            // Played
            $stats[$homeId]['played']++;
            $stats[$awayId]['played']++;

            // Goals
            $stats[$homeId]['gf'] += $homeGoals;
            $stats[$homeId]['ga'] += $awayGoals;
            $stats[$awayId]['gf'] += $awayGoals;
            $stats[$awayId]['ga'] += $homeGoals;

            // Points and W/D/L
            if ($homeGoals > $awayGoals) {
                $stats[$homeId]['wins']++;
                $stats[$homeId]['points'] += 3;
                $stats[$awayId]['losses']++;
                $h2hHome = 3; $h2hAway = 0;
            } elseif ($homeGoals < $awayGoals) {
                $stats[$awayId]['wins']++;
                $stats[$awayId]['points'] += 3;
                $stats[$homeId]['losses']++;
                $h2hHome = 0; $h2hAway = 3;
            } else { // draw
                $stats[$homeId]['draws']++;
                $stats[$awayId]['draws']++;
                $stats[$homeId]['points'] += 1;
                $stats[$awayId]['points'] += 1;
                $h2hHome = 1; $h2hAway = 1;
            }

            // Record head-to-head points per opponent
            $stats[$homeId]['h2h'][$awayId] = ($stats[$homeId]['h2h'][$awayId] ?? 0) + $h2hHome;
            $stats[$awayId]['h2h'][$homeId] = ($stats[$awayId]['h2h'][$homeId] ?? 0) + $h2hAway;

            // Average earliest start time: use start_datetime if available
            if ($match->start_datetime) {
                $ts = $match->start_datetime->getTimestamp();
                $stats[$homeId]['start_ts_sum'] += $ts;
                $stats[$homeId]['start_ts_count'] += 1;
                $stats[$awayId]['start_ts_sum'] += $ts;
                $stats[$awayId]['start_ts_count'] += 1;
            }
        }

        // Finalize derived fields
        foreach ($stats as $id => &$row) {
            $row['gd'] = $row['gf'] - $row['ga'];
            if ($row['start_ts_count'] > 0) {
                $row['avg_start_ts'] = (int)floor($row['start_ts_sum'] / $row['start_ts_count']);
            } else {
                $row['avg_start_ts'] = null; // no games yet
            }
            unset($row['start_ts_sum'], $row['start_ts_count']);
        }
        unset($row);

        // Convert to collection for grouping and sorting
        $rows = collect(array_values($stats));

        // Group by total points, apply tie-breakers within each group
        $grouped = $rows->groupBy('points')->sortKeysDesc();
        $sorted = collect(); // second sort

        // We are going from better to worse. If we have only one with x points, then just concat that to sorted list
        foreach ($grouped as $points => $group) {
            if ($group->count() === 1) {
                $sorted = $sorted->concat($group->values());
                continue;
            }

            $idsInGroup = $group->pluck('team_id')->all();

            // Compute head-to-head points within the group for each team
            $group = $group->map(function ($row) use ($idsInGroup) {
                $h2hPoints = 0;
                foreach ($idsInGroup as $oppId) {
                    if ($oppId === $row['team_id']) continue; // if it is the same team
                    $h2hPoints += $row['h2h'][$oppId] ?? 0;
                }
                $row['h2h_points_in_group'] = $h2hPoints;
                return $row;
            });

            // Sort by: H2H desc, GD desc, GF desc, avg_start asc (nulls last), team_name asc, team_id asc
            $group = $group->sort(function ($a, $b) {
                // H2H points in group
                if ($a['h2h_points_in_group'] !== $b['h2h_points_in_group']) {
                    return $b['h2h_points_in_group'] <=> $a['h2h_points_in_group'];
                }
                // Goal difference
                if ($a['gd'] !== $b['gd']) {
                    return $b['gd'] <=> $a['gd'];
                }
                // Goals for
                if ($a['gf'] !== $b['gf']) {
                    return $b['gf'] <=> $a['gf'];
                }
                // Average start time (earlier = better). Nulls last.
                $aTs = $a['avg_start_ts'];
                $bTs = $b['avg_start_ts'];
                if ($aTs === null && $bTs !== null) return 1; // a after b
                if ($aTs !== null && $bTs === null) return -1; // a before b
                if ($aTs !== null && $bTs !== null && $aTs !== $bTs) {
                    return $aTs <=> $bTs; // earlier (smaller) first
                }
                // Fallback if they are the same
                return $a['team_id'] <=> $b['team_id'];
            })->values()->map(function ($row) {
                unset($row['h2h_points_in_group'], $row['h2h']);
                return $row;
            });

            $sorted = $sorted->concat($group);
        }

        // For single-member groups, remove h2h map
        $sorted = $sorted->map(function ($row) {
            unset($row['h2h']);
            return $row;
        })->values();

        // Add positions (1-based). We do not collapse ties; positions are sequential.
        $position = 1;
        $result = [];
        foreach ($sorted as $row) {
            $result[] = [
                'position' => $position++,
                'team_id' => $row['team_id'],
                'team_name' => $row['team_name'],
                'played' => $row['played'],
                'wins' => $row['wins'],
                'draws' => $row['draws'],
                'losses' => $row['losses'],
                'gf' => $row['gf'],
                'ga' => $row['ga'],
                'gd' => $row['gd'],
                'points' => $row['points'],
            ];
        }

        return $result;
    }
}
