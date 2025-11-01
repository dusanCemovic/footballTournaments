<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    /**
     * Create a new tournament.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_datetime' => ['required', 'date'],
            'match_duration_minutes' => ['required', 'integer', 'min:1'],
            'courts' => ['required', 'integer', 'min:1', 'max:4'],
        ]);

        $tournament = Tournament::create($data);

        return response()->json($tournament, Response::HTTP_CREATED);
    }
}
