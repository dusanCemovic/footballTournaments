<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for a football match. Named FootballMatch to avoid PHP 8 reserved keyword conflict with "match".
 * The underlying table is "matches".
 */
class FootballMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'court_number',
        'start_datetime',
        'end_datetime',
        'home_goals',
        'away_goals',
        'is_final',
        'unfinalize_count',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'home_goals' => 'integer',
        'away_goals' => 'integer',
        'is_final' => 'boolean',
        'unfinalize_count' => 'integer',
        'court_number' => 'integer',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
