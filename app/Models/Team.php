<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * @property int $id
 * @property string $name
 * @property float $strength_rating
 * @property string $logo_url
 */
class Team extends Model
{
    use HasFactory;
    protected $table = 'teams';
    protected $fillable = ['name', 'strength_rating', 'logo_url'];

    /**
     * @return HasMany
     */
    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team_home_id');
    }

    /**
     * @return HasMany
     */
    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'team_away_id');
    }

    /**
     * @return HasOne
     */
    public function standing(): HasOne
    {
        return $this->hasOne(LeagueStanding::class);
    }

}
