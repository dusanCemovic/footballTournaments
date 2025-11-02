<?php

namespace Tests\Feature;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\MatchFinishedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_creates_round_robin_schedule(): void
    {
        $tournament = Tournament::factory()->create([
            'courts' => 2,
            'match_duration_minutes' => 20,
            'start_datetime' => now()->addDay(),
        ]);

        // Create 6 teams in this tournament
        Team::factory()->count(6)->create(['tournament_id' => $tournament->id]);

        $resp = $this->postJson("/api/tournaments/{$tournament->id}/schedule:generate");

        $resp->assertOk()
            ->assertJson(['message' => '(re)Schedule generation tournament finished.']);

        // For 6 teams, round-robin has 6*5/2 = 15 matches
        $this->assertDatabaseCount('matches', 15);

        // Check that matches have basic scheduling fields
        $any = FootballMatch::where('tournament_id', $tournament->id)->first();
        $this->assertNotNull($any);
        $this->assertNotNull($any->court_number);
        $this->assertNotNull($any->start_datetime);
        $this->assertNotNull($any->end_datetime);
    }

    public function test_regeneration_blocked_if_any_match_is_final(): void
    {
        $tournament = Tournament::factory()->create([
            'courts' => 1,
            'match_duration_minutes' => 20,
            'start_datetime' => now()->addDay(),
        ]);
        $teams = Team::factory()->count(2)->create(['tournament_id' => $tournament->id]);

        // first try should be succesfull
        $resp = $this->postJson("/api/tournaments/{$tournament->id}/schedule:generate");
        $resp->assertOk()
            ->assertJson(['message' => '(re)Schedule generation tournament finished.']);

        // finish all matches, in this situation only one
        MatchFinishedService::run($tournament, true);

        // second try should be blocked
        $resp = $this->postJson("/api/tournaments/{$tournament->id}/schedule:generate");

        $resp->assertStatus(422)
            ->assertJson(['message' => 'Schedule regeneration is not allowed because there are matches with final results.']);
    }
}
