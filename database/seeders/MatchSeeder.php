<?php

namespace Database\Seeders;

use App\Models\FootballMatch;
use App\Models\Tournament;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Random\RandomException;

class MatchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @throws RandomException
     */
    public function run(): void
    {
        // Simple version: finalize a random number of earliest scheduled matches per tournament.
        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            /** @var Collection<int, FootballMatch> $matches */
            $matches = FootballMatch::query()
                ->where('tournament_id', $tournament->id)
                ->whereNotNull('start_datetime') // only scheduled games
                ->orderBy('start_datetime')
                ->orderBy('id') // stable tie-breaker
                ->get();

            if ($matches->isEmpty()) {
                continue; // No schedule yet
            }

            // Pick a random count N in [0, total scheduled matches]
            $n = random_int(0, $matches->count());
            if ($n === 0) {
                continue; // finalize none
            }

            $toFinalize = $matches->take($n);

            foreach ($toFinalize as $match) {
                if ($match->is_final) {
                    continue; // don't overwrite finals
                }

                // Generate random plausible football score (0-5 goals each)
                $home = random_int(0, 5);
                $away = random_int(0, 5);

                $match->home_goals = $home;
                $match->away_goals = $away;
                $match->is_final = true;
                $match->save();
            }
        }
    }
}
