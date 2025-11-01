<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->catchPhrase() . ' Cup',
            'courts' => $this->faker->numberBetween(1, 4),
            'match_duration_minutes' => $this->faker->randomElement([20, 25, 30, 35, 40]),
            'start_datetime' => $this->faker->dateTimeBetween('+1 days', '+2 weeks'),
        ];
    }
}
