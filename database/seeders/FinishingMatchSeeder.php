<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Services\MatchFinishedService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinishingMatchSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Simple version: finalize a random number of earliest scheduled matches per tournament.
        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            MatchFinishedService::run($tournament);
        }
    }
}
