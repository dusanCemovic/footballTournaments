<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MatchController extends Controller
{
    /**
     * Enter a result for a match.
     */
    public function storeResult(Request $request, FootballMatch $match)
    {
        $data = $request->validate([
            'home_goals' => ['required', 'integer', 'min:0'],
            'away_goals' => ['required', 'integer', 'min:0'],
        ]);

        if ($match->is_final) {
            return response()->json(['message' => 'Result is final and cannot be changed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // match is finished and we are making it like final.
        // maybe we don't need is_final since we have result which mark this match as final. Due the business logic, we mark this as final
        $match->fill($data);
        $match->is_final = true;
        $match->save();

        return response()->json($match);
    }

    /**
     * Unfinalize a match result (allowed at most once).
     */
    public function unfinalize(FootballMatch $match)
    {
        if ($match->unfinalize_count >= 1) {
            return response()->json(['message' => 'Match result can only be unfinalized once.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Clear results and mark as not final
        $match->home_goals = null;
        $match->away_goals = null;
        $match->is_final = false;
        $match->unfinalize_count = $match->unfinalize_count + 1;
        $match->save();

        return response()->json($match);
    }
}
