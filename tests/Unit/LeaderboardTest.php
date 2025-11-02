<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\LeaderboardService;
use App\Services\MatchFinishedService;
use App\Services\ScheduleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Leaderboard across random tournaments.
     * A team that wins all of its matches ranks first
     */
    public function test_leaderboard_points_and_dominance_invariants(): void
    {
        for ($trial = 0; $trial < 6; $trial++) {
            $teamCount = random_int(2, 8);

            $tournament = Tournament::factory()->create([
                'courts' => random_int(1, 4),
                'match_duration_minutes' => random_int(20, 45),
                'start_datetime' => now()->addDay(),
            ]);

            $teams = Team::factory()->count($teamCount)->create(['tournament_id' => $tournament->id])->values();

            // Choose a champion team that wins all of its matches
            $champ = $teams[random_int(0, $teamCount - 1)];

            // Schedule all games
            ScheduleGeneratorService::generateForTournament($tournament);

            // Champion beats every other team once
            $champMatches = FootballMatch::query()
                ->where('tournament_id', $tournament->id)
                ->where(function ($q) use ($champ) {
                    $q->where('home_team_id', $champ->id)
                      ->orWhere('away_team_id', $champ->id);
                })
                ->get();

            foreach ($champMatches as $m) {
                if ($m->home_team_id === $champ->id) {
                    $m->home_goals = 3;
                    $m->away_goals = 0;
                } else {
                    $m->home_goals = 0;
                    $m->away_goals = 3;
                }
                $m->is_final = true;
                $m->save();
            }

            // finish all other
            MatchFinishedService::run($tournament, true);

            // make calculation for other teams
            $rows = LeaderboardService::calculateForTournament($tournament);

            // Assert champion is first
            $this->assertNotEmpty($rows);
            $this->assertSame($champ->id, $rows[0]['team_id']);

            // Points formula holds for each row
            foreach ($rows as $row) {
                $expectedPoints = 3 * $row['wins'] + 1 * $row['draws'];
                $this->assertSame($expectedPoints, $row['points']);
                $this->assertSame($row['gf'] - $row['ga'], $row['gd']);
            }
        }
    }
}
