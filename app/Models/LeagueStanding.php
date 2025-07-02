<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_id
 * @property int $points
 * @property int $played
 * @property int $won
 * @property int $drawn
 * @property int $lost
 * @property int $goals_for
 * @property int $goals_against
 * @property int $goal_difference
 * @property int $position
 */
class LeagueStanding extends Model
{

    protected $table = 'league_standings';
    protected $fillable = [
        'team_id',
        'points',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
        'goal_difference',
        'position'
    ];

    protected $casts = [
        'points' => 'integer',
        'played' => 'integer',
        'won' => 'integer',
        'drawn' => 'integer',
        'lost' => 'integer',
        'goals_for' => 'integer',
        'goals_against' => 'integer',
        'goal_difference' => 'integer',
        'position' => 'integer'
    ];

    /**
     * @return BelongsTo
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return float|int
     */
    public function getLossPercentageAttribute(): float|int
    {
        return $this->played > 0 ? round(($this->lost / $this->played) * 100, 1) : 0;
    }

    /**
     * @return float|int
     */
    public function getWinPercentageAttribute(): float|int
    {
        return $this->played > 0 ? round(($this->won / $this->played) * 100, 1) : 0;
    }

    /**
     * @return float|int
     */
    public function getPointsPerGameAttribute(): float|int
    {
        return $this->played > 0 ? round($this->points / $this->played, 2) : 0;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeOrdered($query): mixed
    {
        return $query->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc');
    }

    public function scopeWithTeam($query)
    {
        return $query->with('team');
    }
}
