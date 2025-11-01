<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\FootballMatch;

class FootballMatchSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // todo this should be moved to Service for generating
        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            $teams = Team::where('tournament_id', $tournament->id)->get();

            // Generate round-robin matches (each pair plays once)
            $teamIds = $teams->pluck('id')->values();
            // todo make them random so it is not that first will play first
            $n = $teamIds->count();
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $homeId = $teamIds[$i];
                    $awayId = $teamIds[$j];

                    // Randomly swap home/away to add variety
                    if (random_int(0, 1) === 1) {
                        [$homeId, $awayId] = [$awayId, $homeId];
                    }

                    // todo game can't be played on same court on the same time EDGE CASE maybe
                    // todo maybe to schedule courts after matches are created
                    // todo make sure that team are player per round
                    // todo odd number of teams EDGE CASE

                    FootballMatch::create([
                        'tournament_id' => $tournament->id,
                        'home_team_id' => $homeId,
                        'away_team_id' => $awayId,
                        'court_number' => null,
                        'start_datetime' => null,
                        'home_goals' => null,
                        'away_goals' => null,
                        'is_final' => false,
                        'unfinalize_count' => 0,
                    ]);
                }
            }
        }
    }
}
