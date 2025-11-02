<?php

namespace Tests\Feature;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_returns_positions_and_basic_ordering(): void
    {
        $tournament = Tournament::factory()->create();
        [$a, $b] = Team::factory()->count(2)->create(['tournament_id' => $tournament->id]);

        // One final match: A beats B
        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $a->id,
            'away_team_id' => $b->id,
            'court_number' => 1,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addMinutes(30),
            'home_goals' => 2,
            'away_goals' => 0,
            'is_final' => true,
            'unfinalize_count' => 0,
        ]);

        $resp = $this->getJson("/api/tournaments/{$tournament->id}/leaderboard");

        $resp->assertOk()
            ->assertJsonStructure([
                'tournament_id',
                'leaderboard' => [
                    ['position','team_id','team_name','played','wins','draws','losses','gf','ga','gd','points']
                ],
                'message'
            ]);

        $data = $resp->json('leaderboard');
        $this->assertCount(2, $data);
        $this->assertSame(1, $data[0]['position']);
        $this->assertSame($a->id, $data[0]['team_id']);
        $this->assertSame(3, $data[0]['points']);
    }
}
