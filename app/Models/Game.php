<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $team_home_id
 * @property int $team_away_id
 * @property int|null $home_goals
 * @property int|null $away_goals
 * @property int $week
 * @property int $is_finished
 */

class Game extends Model
{

    use HasFactory;
    protected $table = 'games';
    protected $fillable = ['team_home_id', 'team_away_id', 'home_goals', 'away_goals', 'week', 'is_finished', 'played_at'];

    /**
     * @return BelongsTo
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_home_id');
    }

    /**
     * @return BelongsTo
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_away_id');
    }

    public function getResultAttribute()
    {
        if (!$this->is_finished) {
            return 'vs';
        }

        return $this->home_goals . '-' . $this->away_goals;
    }

    public function getWinnerAttribute()
    {
        if ($this->status !== 'completed') {
            return null;
        }

        if ($this->home_goals > $this->away_goals) {
            return 'home';
        } elseif ($this->away_goals > $this->home_goals) {
            return 'away';
        }

        return 'draw';
    }

    public function getResultForTeam($teamId)
    {
        if (!$this->is_finished) {
            return null;
        }

        $isHome = $this->team_home_id == $teamId;
        $teamGoals = $isHome ? $this->home_goals : $this->away_goals;
        $opponentGoals = $isHome ? $this->away_goals : $this->home_goals;

        if ($teamGoals > $opponentGoals) {
            return 'win';
        } elseif ($teamGoals < $opponentGoals) {
            return 'loss';
        }

        return 'draw';
    }

    public function scopeFinished($query)
    {
        return $query->where('is_finished', 1);
    }

    public function scopePending($query)
    {
        return $query->where('is_finished', 0);
    }

    public function scopeByWeek($query, $week)
    {
        return $query->where('week', $week);
    }

    public function scopeWithTeams($query)
    {
        return $query->with(['homeTeam', 'awayTeam']);
    }

}
