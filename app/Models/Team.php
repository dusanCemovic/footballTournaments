<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'name',
    ];

    /**
     * Tournament this team belongs to.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Matches where this team is the home team.
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    /**
     * Matches where this team is the away team.
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    /**
     * All matches for this team (home and away).
     */
    public function matches()
    {
        return FootballMatch::query()->where(function ($q) {
            $q->where('home_team_id', $this->id)
              ->orWhere('away_team_id', $this->id);
        });
    }
}
