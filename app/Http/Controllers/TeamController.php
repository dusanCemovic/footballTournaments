<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /**
     * Add a team to a tournament.
     */
    public function store(Request $request, Tournament $tournament)
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('teams', 'name')->where(fn($q) => $q->where('tournament_id', $tournament->id)),
                // todo when storing team, we should check if we have that team EDGE CASE
                // todo check what if somebody insert same name all wrong tournament id
            ],
        ]);

        $team = $tournament->teams()->create($data);

        return response()->json($team, Response::HTTP_CREATED);
    }

    /**
     * Delete a team if there are no matches with final results yet.
     */
    public function destroy(Tournament $tournament, Team $team)
    {
        // Ensure the team belongs to the provided tournament route param
        if ($team->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Team does not belong to this tournament.'], Response::HTTP_NOT_FOUND);
        }

        // A team can only be deleted if there are no matches with results (final) yet on that tournament
        $hasFinalMatches = $team->matches()->where('is_final', true)->where('tournament_id', $tournament->id)->exists();
        // TODO EDGE CASES deleting team that has final result
        if ($hasFinalMatches) {
            return response()->json(['message' => 'Team cannot be deleted because it has matches with final results.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $team->delete();

        return response()->json(['message' => 'Team deleted.']);
    }
}
