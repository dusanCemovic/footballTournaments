<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tournament;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // For each tournament create 6-8 teams
        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            $teamCount = rand(6, 8);
            Team::factory()->count($teamCount)->create([
                'tournament_id' => $tournament->id,
            ]);
        }
    }
}
