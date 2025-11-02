<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_two_team_leaderboard(): void
    {
        // Arrange: tournament with 2 teams and one final match
        $tournament = Tournament::factory()->create([
            'courts' => 1,
            'match_duration_minutes' => 25,
        ]);

        $teamA = Team::factory()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Better FC',
        ]);
        $teamB = Team::factory()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Worse FC',
        ]);

        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'court_number' => 1,
            'start_datetime' => now()->addHour(),
            'end_datetime' => now()->addHours(2),
            'home_goals' => 2,
            'away_goals' => 1,
            'is_final' => true,
            'unfinalize_count' => 0,
        ]);

        // Act
        $rows = LeaderboardService::calculateForTournament($tournament);

        // Assert
        $this->assertCount(2, $rows);
        $this->assertSame(1, $rows[0]['position']);
        $this->assertSame($teamA->id, $rows[0]['team_id']);
        $this->assertSame(3, $rows[0]['points']);
        $this->assertSame(1, $rows[0]['played']);
        $this->assertSame(1, $rows[0]['wins']);
        $this->assertSame(0, $rows[0]['draws']);
        $this->assertSame(0, $rows[0]['losses']);
        $this->assertSame(2, $rows[0]['gf']);
        $this->assertSame(1, $rows[0]['ga']);
        $this->assertSame(1, $rows[0]['gd']);

        $this->assertSame(2, $rows[1]['position']);
        $this->assertSame($teamB->id, $rows[1]['team_id']);
        $this->assertSame(0, $rows[1]['points']);
    }

    public function test_tie_breakers_goal_difference_then_goals_for(): void
    {
        $tournament = Tournament::factory()->create();
        $a = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'A']);
        $b = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'B']);
        $c = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'C']);

        // Create a mini table where all end on same points but different GD/GF
        // A beats B 3-0  -> A +3 GD, 3 pts
        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $a->id,
            'away_team_id' => $b->id,
            'is_final' => true,
            'home_goals' => 3,
            'away_goals' => 0,
        ]);
        // B beats C 2-0  -> B +2 GD, 3 pts
        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $b->id,
            'away_team_id' => $c->id,
            'is_final' => true,
            'home_goals' => 2,
            'away_goals' => 0,
        ]);
        // C beats A 1-0  -> C +1 GD, 3 pts
        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $c->id,
            'away_team_id' => $a->id,
            'is_final' => true,
            'home_goals' => 1,
            'away_goals' => 0,
        ]);

        $rows = LeaderboardService::calculateForTournament($tournament);

        // A should be first (best GD +2 overall), then B (-1), then C (-1)
        $this->assertSame([$a->id, $b->id, $c->id], array_column($rows, 'team_id'));

        // Additionally assert exact goal differences per team for clarity
        $byTeam = collect($rows)->keyBy('team_id');
        $this->assertSame(2, $byTeam[$a->id]['gd']);
        $this->assertSame(-1, $byTeam[$b->id]['gd']);
        $this->assertSame(-1, $byTeam[$c->id]['gd']);
    }
}
