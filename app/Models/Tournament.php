<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'courts',
        'match_duration_minutes',
        'start_datetime',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'courts' => 'integer',
        'match_duration_minutes' => 'integer',
    ];

    /**
     * Teams in the tournament.
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Matches scheduled for the tournament.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'tournament_id');
    }
}
