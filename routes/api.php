<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\LeaderboardController;

Route::post('/tournaments', [TournamentController::class, 'store']);
// Example:
// curl -X POST http://127.0.0.1:8000/api/tournaments \
//   -H "Content-Type: application/json" \
//   -d '{
//         "name": "Summer Cup",
//         "start_datetime": "2025-11-10 09:00:00",
//         "match_duration_minutes": 30,
//         "courts": 2
//       }'

// Note on existence checks:
// All routes below use Laravel's implicit route model binding (e.g., Tournament $tournament).
// If an ID does not exist, Laravel will automatically return a 404 Not Found response.
// We additionally constrain route parameters to be numeric to avoid unnecessary binding attempts.
Route::post('/tournaments/{tournament}/teams', [TeamController::class, 'store'])
    ->whereNumber('tournament'); // todo do i need this
// Example:
// curl -X POST http://127.0.0.1:8000/api/tournaments/1/teams \
//   -H "Content-Type: application/json" \
//   -d '{
//         "name": "Blue Tigers FC"
//       }'
Route::delete('/tournaments/{tournament}/teams/{team}', [TeamController::class, 'destroy'])
    ->whereNumber('tournament')
    ->whereNumber('team');
// Example:
// curl -X DELETE http://127.0.0.1:8000/api/tournaments/1/teams/5

Route::post('/tournaments/{tournament}/schedule:generate', [ScheduleController::class, 'generate'])
    ->whereNumber('tournament');
// Example:
// curl -X POST http://127.0.0.1:8000/api/tournaments/1/schedule:generate

Route::post('/matches/{match}/result', [MatchController::class, 'storeResult'])
    ->whereNumber('match');
// Example:
// curl -X POST http://127.0.0.1:8000/api/matches/42/result \
//   -H "Content-Type: application/json" \
//   -d '{
//         "home_goals": 2,
//         "away_goals": 1
//       }'
Route::post('/matches/{match}/unfinalize', [MatchController::class, 'unfinalize'])
    ->whereNumber('match');
// Example:
// curl -X POST http://127.0.0.1:8000/api/matches/42/unfinalize

Route::get('/tournaments/{tournament}/leaderboard', [LeaderboardController::class, 'index'])
    ->whereNumber('tournament');
// Example:
// curl http://127.0.0.1:8000/api/tournaments/1/leaderboard
