<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class TournamentStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * It creates a tournament successfully with valid payload.
     */
    public function test_it_creates_tournament_successfully(): void
    {
        $payload = [
            'name' => 'Test Cup',
            'start_datetime' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'match_duration_minutes' => 30,
            'courts' => 2,
        ];

        $response = $this->postJson('/api/tournaments', $payload);

        $response
            ->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('name', $payload['name'])
                ->where('match_duration_minutes', $payload['match_duration_minutes'])
                ->where('courts', $payload['courts'])
                ->hasAll(['id', 'created_at', 'updated_at', 'start_datetime'])
            );

        $this->assertDatabaseHas('tournaments', [
            'name' => $payload['name'],
            'match_duration_minutes' => $payload['match_duration_minutes'],
            'courts' => $payload['courts'],
        ]);
    }

    /**
     * It fails with 422 when required field is missing.
     */
    public function test_it_fails_validation_when_required_field_missing(): void
    {
        // Missing name
        $payload = [
            // 'name' => 'Missing',
            'start_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'match_duration_minutes' => 25,
            'courts' => 1,
        ];

        $response = $this->postJson('/api/tournaments', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * It fails with 422 when courts value is not allowed (e.g., 5 > max 4).
     */
    public function test_it_fails_validation_when_courts_out_of_range(): void
    {
        $payload = [
            'name' => 'Winter Cup',
            'start_datetime' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'match_duration_minutes' => 20,
            'courts' => 5, // invalid, max 4
        ];

        $response = $this->postJson('/api/tournaments', $payload);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['courts']);
    }
}
