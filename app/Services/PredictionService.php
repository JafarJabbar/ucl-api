<?php
namespace App\Services;

use App\Models\Game;
use App\Models\LeagueStanding;

class PredictionService {
    public function predictFinalTable(): array
    {
        $currentStandings = LeagueStanding::orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        $totalMatches = Game::count();
        $completedMatches = Game::where('is_finished', 1)->count();
        $isSeasonComplete = $totalMatches > 0 && $completedMatches === $totalMatches;

        $predictions = [];

        foreach ($currentStandings as $index => $standing) {
            if ($isSeasonComplete) {
                $predictions[] = [
                    'team_id' => $standing->team_id,
                    'team_name' => $standing->team?->name,
                    'current_points' => $standing->points,
                    'projected_points' => $standing->points, // Same as current when complete
                    'final_position' => $index + 1,
                    'championship_probability' => $index === 0 ? 1.0 : 0.0, // 100% for winner, 0% for others
                    'is_season_complete' => true
                ];
            } else {
                // Season in progress - show predictions
                $remainingMatches = $this->getRemainingMatches($standing->team_id);
                $projectedPoints = $this->projectPoints($standing, $remainingMatches);

                $predictions[] = [
                    'team_id' => $standing->team_id,
                    'team_name' => $standing->team?->name,
                    'current_points' => $standing->points,
                    'projected_points' => $projectedPoints,
                    'championship_probability' => $this->calculateChampionshipProbability($standing, $projectedPoints),
                    'is_season_complete' => false
                ];
            }
        }

        return [
            'predictions' => $predictions,
            'season_complete' => $isSeasonComplete,
            'matches_completed' => $completedMatches,
            'total_matches' => $totalMatches
        ];
    }

    private function projectPoints($standing, $remainingMatches) {
        $team = $standing->team;
        $projectedPoints = $standing->points;

        foreach ($remainingMatches as $match) {
            $winProbability = $this->calculateWinProbability($team, $match->opponent);
            $drawProbability = 0.25;

            $expectedPoints = ($winProbability * 3) + ($drawProbability);
            $projectedPoints += $expectedPoints;
        }

        return $projectedPoints;
    }

    private function getRemainingMatches($teamId) {
        return Game::where('is_finished', 0)
            ->where(function($query) use ($teamId) {
                $query->where('team_home_id', $teamId)
                    ->orWhere('team_away_id', $teamId);
            })
            ->with(['homeTeam', 'awayTeam'])
            ->get()
            ->map(function($match) use ($teamId) {
                $opponent = ($match->team_home_id == $teamId)
                    ? $match->awayTeam
                    : $match->homeTeam;

                return (object)[
                    'match' => $match,
                    'opponent' => $opponent,
                    'is_home' => $match->team_home_id == $teamId
                ];
            });
    }

    private function calculateWinProbability($team, $opponent) {
        $teamStrength = $team->strength_rating;
        $opponentStrength = $opponent->strength_rating;

        $strengthDifference = $teamStrength - $opponentStrength;

        $probability = 1 / (1 + exp(-$strengthDifference * 5));

        return max(0.1, min(0.9, $probability));
    }

    /**
     * @param $standing
     * @param $projectedPoints
     * @return float
     */
    private function calculateChampionshipProbability($standing, $projectedPoints): float
    {
        $allProjections = $this->getAllProjectedPoints();

        $betterTeams = 0;
        foreach ($allProjections as $projection) {
            if ($projection['projected_points'] > $projectedPoints) {
                $betterTeams++;
            }
        }

        if ($betterTeams == 0) {
            return 0.8;
        } elseif ($betterTeams == 1) {
            return 0.3;
        } elseif ($betterTeams == 2) {
            return 0.1;
        } else {
            return 0.01;
        }
    }

    /**
     * @return array
     */
    private function getAllProjectedPoints(): array
    {
        $standings = LeagueStanding::with('team')->get();
        $projections = [];

        foreach ($standings as $standing) {
            $remainingMatches = $this->getRemainingMatches($standing->team_id);
            $projectedPoints = $this->projectPoints($standing, $remainingMatches);

            $projections[] = [
                'team_id' => $standing->team_id,
                'projected_points' => $projectedPoints
            ];
        }

        return $projections;
    }
}
