<?php

namespace Tests\Feature;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use App\Services\ScheduleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class TeamRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reschedule_on_team_delete_when_no_finals_and_schedule_exists(): void
    {
        // Arrange: tournament with 4 teams and an existing schedule (no finals)
        $tournament = Tournament::factory()->create([
            'courts' => 2,
            'match_duration_minutes' => 30,
            'start_datetime' => now()->addDay(),
        ]);

        $teams = Team::factory()->count(4)->create([
            'tournament_id' => $tournament->id,
        ])->values();

        // Generate initial schedule for 4 teams -> 4*3/2 = 6 matches
        ScheduleGeneratorService::generateForTournament($tournament);
        $this->assertSame(6, $tournament->matches()->count(), 'Precondition: 6 matches scheduled for 4 teams');

        // Act: delete one team via API; since no finals exist, controller should allow deletion and reschedule
        $toDelete = $teams[0];
        $resp = $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$toDelete->id}");
        $resp->assertOk()->assertJson(['message' => 'Team deleted.']);

        // Assert: schedule is regenerated for remaining 3 teams -> 3*2/2 = 3 matches
        $matches = $tournament->matches()->get();
        $this->assertCount(3, $matches, 'After deletion, schedule should be regenerated for 3 teams');

        // No match should reference the deleted team
        $this->assertSame(0, $matches->where('home_team_id', $toDelete->id)->count());
        $this->assertSame(0, $matches->where('away_team_id', $toDelete->id)->count());
    }

    public function test_store_team_successfully(): void
    {
        $tournament = Tournament::factory()->create();

        $resp = $this->postJson("/api/tournaments/{$tournament->id}/teams", [
            'name' => 'Blue Tigers FC',
        ]);

        $resp->assertCreated()
            ->assertJsonFragment(['name' => 'Blue Tigers FC']);

        $this->assertDatabaseHas('teams', [
            'tournament_id' => $tournament->id,
            'name' => 'Blue Tigers FC',
        ]);
    }

    public function test_store_team_duplicate_name_in_same_tournament_fails(): void
    {
        $tournament = Tournament::factory()->create();
        Team::factory()->create([
            'tournament_id' => $tournament->id,
            'name' => 'Random Team FC',
        ]);

        $resp = $this->postJson("/api/tournaments/{$tournament->id}/teams", [
            'name' => 'Random Team FC',
        ]);

        $resp->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_team_on_nonexistent_tournament_returns_404(): void
    {
        $resp = $this->postJson("/api/tournaments/9999/teams", [
            'name' => 'Ghosts FC',
        ]);

        // for this i had to add new exception in bootstrap/api.php
        $resp->assertStatus(404)
            ->assertJson(['message' => 'Resource not found.']);
    }

    public function test_delete_team_success_when_no_final_matches(): void
    {
        $tournament = Tournament::factory()->create();
        $team = Team::factory()->create(['tournament_id' => $tournament->id]);

        $resp = $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$team->id}");

        $resp->assertOk()
            ->assertJson(['message' => 'Team deleted.']);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_delete_team_fails_when_team_has_final_match(): void
    {
        $tournament = Tournament::factory()->create();
        $teamA = Team::factory()->create(['tournament_id' => $tournament->id]);
        $teamB = Team::factory()->create(['tournament_id' => $tournament->id]);
        $teamC = Team::factory()->create(['tournament_id' => $tournament->id]);

        // Create a final match involving teamA in the same tournament
        FootballMatch::create([
            'tournament_id' => $tournament->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'court_number' => 1,
            'start_datetime' => now(),
            'end_datetime' => now()->addMinutes(30),
            'home_goals' => 1,
            'away_goals' => 0,
            'is_final' => true,
            'unfinalize_count' => 0,
        ]);

        $resp = $this->deleteJson("/api/tournaments/{$tournament->id}/teams/{$teamC->id}");

        $resp->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'Team cannot be deleted because tournament has matches with final results.']);

        $this->assertDatabaseHas('teams', ['id' => $teamA->id]);
    }

    public function test_delete_team_returns_404_if_team_not_in_tournament(): void
    {
        $tournamentA = Tournament::factory()->create();
        $tournamentB = Tournament::factory()->create();
        $teamInB = Team::factory()->create(['tournament_id' => $tournamentB->id]);

        $resp = $this->deleteJson("/api/tournaments/{$tournamentA->id}/teams/{$teamInB->id}");

        $resp->assertStatus(404)
            ->assertJson(['message' => 'Team does not belong to this tournament.']);
    }
}
