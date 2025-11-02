<?php

namespace Tests\Unit;

use App\Models\Team;
use App\Models\Tournament;
use App\Services\ScheduleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test for schedule invariants across random tournaments.
     * Invariants:
     *  - No team overlaps in time
     *  - Courts are not double-booked
     *  - Round-robin completeness: total matches = n*(n-1)/2
     */
    public function test_schedule_invariants_property_based(): void
    {
        // Run multiple randomized trials
        for ($trial = 0; $trial < 8; $trial++) {
            $teamCount = random_int(2, 10);
            $courts = random_int(1, 4);
            $slot = random_int(20, 45);

            $tournament = Tournament::factory()->create([
                'courts' => $courts,
                'match_duration_minutes' => $slot,
                'start_datetime' => now()->addDay(),
            ]);

            Team::factory()->count($teamCount)->create([
                'tournament_id' => $tournament->id,
            ]);

            // Generate schedule
            ScheduleGeneratorService::generateForTournament($tournament);

            $matches = $tournament->matches()->orderBy('start_datetime')->get();

            // Expected total matches for round-robin
            $expected = $teamCount * ($teamCount - 1) / 2;
            $this->assertCount($expected, $matches, 'Total matches should equal N*(N-1)/2');

            // Build maps
            $byTeam = [];
            $byCourt = [];
            foreach ($matches as $m) {
                // Basic sanity
                $this->assertNotNull($m->court_number);
                $this->assertGreaterThanOrEqual(1, $m->court_number);
                $this->assertLessThanOrEqual($courts, $m->court_number);
                $this->assertNotNull($m->start_datetime);
                $this->assertNotNull($m->end_datetime);

                // get all games
                $byTeam[$m->home_team_id][] = [$m->start_datetime->getTimestamp(), $m->end_datetime->getTimestamp(), $m->id];
                $byTeam[$m->away_team_id][] = [$m->start_datetime->getTimestamp(), $m->end_datetime->getTimestamp(), $m->id];
                $byCourt[$m->court_number][] = [$m->start_datetime->getTimestamp(), $m->end_datetime->getTimestamp(), $m->id];
            }

            // Invariant: a team cannot have overlapping intervals
            foreach ($byTeam as $teamId => $intervals) {
                // sort all games by intervals
                usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
                for ($i = 1; $i < count($intervals); $i++) {
                    [$prevStart, $prevEnd] = $intervals[$i-1];
                    [$curStart, $curEnd] = $intervals[$i];
                    // treat as [start, end) — end equals next start is OK
                    $this->assertGreaterThanOrEqual($prevEnd, $curStart, "Team {$teamId} has overlapping matches");
                }
            }

            // Invariant: a court cannot be double-booked
            foreach ($byCourt as $court => $intervals) {
                usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);
                for ($i = 1; $i < count($intervals); $i++) {
                    [$prevStart, $prevEnd] = $intervals[$i-1];
                    [$curStart, $curEnd] = $intervals[$i];
                    $this->assertGreaterThanOrEqual($prevEnd, $curStart, "Court {$court} is double-booked");
                }
            }
        }
    }
}
