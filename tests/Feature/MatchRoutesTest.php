<?php

namespace Tests\Feature;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class MatchRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_result_success_and_cannot_post_again_when_final(): void
    {
        $tournament = Tournament::factory()->create();
        [$home, $away] = Team::factory()->count(2)->create(['tournament_id' => $tournament->id]);

        $match = FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'court_number' => 1,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addMinutes(30),
            'is_final' => false,
            'unfinalize_count' => 0,
        ]);

        $resp = $this->postJson("/api/matches/{$match->id}/result", [
            'home_goals' => 2,
            'away_goals' => 1,
        ]);

        $resp->assertOk()
            ->assertJsonFragment(['home_goals' => 2, 'away_goals' => 1, 'is_final' => true]);

        // Try to post again
        $resp2 = $this->postJson("/api/matches/{$match->id}/result", [
            'home_goals' => 3,
            'away_goals' => 3,
        ]);
        $resp2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'Result is final and cannot be changed.']);
    }

    public function test_unfinalize_once_then_second_time_fails(): void
    {
        $tournament = Tournament::factory()->create();
        [$home, $away] = Team::factory()->count(2)->create(['tournament_id' => $tournament->id]);

        $match = FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'court_number' => 1,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addMinutes(30),
            'home_goals' => 1,
            'away_goals' => 0,
            'is_final' => true,
            'unfinalize_count' => 0,
        ]);

        // Unfinalize first time - should succeed
        $resp = $this->postJson("/api/matches/{$match->id}/unfinalize");
        $resp->assertOk();

        $match->refresh();
        $this->assertFalse($match->is_final);
        $this->assertNull($match->home_goals);
        $this->assertNull($match->away_goals);
        $this->assertSame(1, $match->unfinalize_count);

        // Second unfinalize - should fail
        $resp2 = $this->postJson("/api/matches/{$match->id}/unfinalize");
        $resp2->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'Match result can only be unfinalized once.']);
    }

    /*
     * Testing that away goals are not in, or goals try to be negative
     */
    public function test_validation_errors_on_result_payload(): void
    {
        $tournament = Tournament::factory()->create();
        [$home, $away] = Team::factory()->count(2)->create(['tournament_id' => $tournament->id]);
        $match = FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'court_number' => 1,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addMinutes(30),
            'is_final' => false,
            'unfinalize_count' => 0,
        ]);

        // Missing away_goals
        $resp = $this->postJson("/api/matches/{$match->id}/result", [
            'home_goals' => 1,
        ]);
        $resp->assertStatus(422)->assertJsonValidationErrors(['away_goals']);

        // Negative values
        $resp2 = $this->postJson("/api/matches/{$match->id}/result", [
            'home_goals' => -1,
            'away_goals' => -3,
        ]);
        $resp2->assertStatus(422)->assertJsonValidationErrors(['home_goals', 'away_goals']);
    }
}
