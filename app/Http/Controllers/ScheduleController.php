<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Services\ScheduleGeneratorService;
use Illuminate\Http\Response;

class ScheduleController extends Controller
{
    public function generate(Tournament $tournament, ScheduleGeneratorService $generator)
    {
        // If schedule already exists and any match contains a final result, re-generation is not allowed.
        $hasFinal = $tournament->matches()->where('is_final', true)->exists();
        if ($hasFinal) {
            return response()->json([
                'message' => 'Schedule regeneration is not allowed because there are matches with final results.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Call the generator service
        try {
            $generator::generateForTournament($tournament);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => '(re)Schedule generation tournament false: ' . $th->getMessage(),
            ]);
        }

        return response()->json([
            'message' => '(re)Schedule generation tournament finished.'
        ]);
    }
}
