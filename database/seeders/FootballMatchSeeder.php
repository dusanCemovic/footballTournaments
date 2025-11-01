<?php

namespace Database\Seeders;

use App\Services\ScheduleGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tournament;

class FootballMatchSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            ScheduleGeneratorService::generateForTournament($tournament);
        }
    }
}
