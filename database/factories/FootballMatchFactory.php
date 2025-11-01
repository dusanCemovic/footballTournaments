<?php

namespace Database\Factories;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FootballMatch>
 */
class FootballMatchFactory extends Factory
{
    protected $model = FootballMatch::class;

    public function definition(): array
    {
        // Create a tournament and two distinct teams in it by default
        $tournament = Tournament::factory()->create();
        $teams = Team::factory()->count(2)->for($tournament)->create();

        return [
            'tournament_id' => $tournament->id,
            'home_team_id' => $teams[0]->id,
            'away_team_id' => $teams[1]->id,
            'court_number' => null,
            'start_datetime' => null,
            'home_goals' => null,
            'away_goals' => null,
            'is_final' => false,
            'unfinalize_count' => 0,
        ];
    }
}
