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
 * @property string $short_name
 * @property int|null $external_id
 */
class Team extends Model
{
    use HasFactory;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'strength_rating',
        'logo_url',
        'short_name',
        'external_id'
    ];

    protected $casts = [
        'strength_rating' => 'float',
        'external_id' => 'integer'
    ];

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
    public function allGames(): HasMany
    {
        return Game::where('team_home_id', $this->id)
            ->orWhere('team_away_id', $this->id);
    }

    public function hasPlayedMatches(): bool
    {
        return $this->homeGames()->where('is_finished', 1)->exists() ||
            $this->awayGames()->where('is_finished', 1)->exists();
    }

    public function getCurrentPosition(): ?int
    {
        return $this->standing?->position;
    }

    public function getWinPercentage(): float
    {
        $standing = $this->standing;
        if (!$standing || $standing->played === 0) {
            return 0.0;
        }

        return round(($standing->won / $standing->played) * 100, 1);
    }

    public function scopeWithStanding($query)
    {
        return $query->with('standing');
    }
}
