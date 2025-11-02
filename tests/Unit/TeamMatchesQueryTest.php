<?php

namespace Tests\Unit;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMatchesQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_query_returns_home_and_away_matches(): void
    {
        $tournament = Tournament::factory()->create();

        $team = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'Target FC']);
        $opponent1 = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'Opp1 FC']);
        $opponent2 = Team::factory()->create(['tournament_id' => $tournament->id, 'name' => 'Opp2 FC']);

        // One as home
        $m1 = FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $team->id,
            'away_team_id' => $opponent1->id,
            'is_final' => false,
        ]);

        // One as away
        $m2 = FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $opponent2->id,
            'away_team_id' => $team->id,
            'is_final' => false,
        ]);

        $found = $team->matches()->orderBy('id')->get();

        $this->assertCount(2, $found);
        $this->assertSame([$m1->id, $m2->id], $found->pluck('id')->all());
    }
}
