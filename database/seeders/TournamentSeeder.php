<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tournament;

class TournamentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create a few tournaments
        Tournament::factory()->count(2)->create();
    }
}
