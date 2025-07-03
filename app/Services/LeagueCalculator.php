<?php
namespace App\Services;

use App\Models\Team;
use App\Models\LeagueStanding;

class LeagueCalculator
{
    public function updateStandings()
    {
        $teams = Team::all();

        foreach ($teams as $team) {
            $stats = $this->calculateTeamStats($team);

            LeagueStanding::updateOrCreate(
                ['team_id' => $team->id],
                $stats
            );
        }

        $this->updatePositions();
    }

    private function calculateTeamStats($team)
    {
        $homeMatches = $team->homeGames()->where('is_finished', 1)->get();
        $awayMatches = $team->awayGames()->where('is_finished', 1)->get();

        $stats = [
            'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 0, 'goals_against' => 0, 'points' => 0
        ];

        foreach ($homeMatches as $match) {
            $stats['played']++;
            $stats['goals_for'] += $match->home_goals;
            $stats['goals_against'] += $match->away_goals;

            if ($match->home_goals > $match->away_goals) {
                $stats['won']++;
                $stats['points'] += 3;
            } elseif ($match->home_goals == $match->away_goals) {
                $stats['drawn']++;
                $stats['points'] += 1;
            } else {
                $stats['lost']++;
            }
        }

        foreach ($awayMatches as $match) {
            $stats['played']++;
            $stats['goals_for'] += $match->away_goals;
            $stats['goals_against'] += $match->home_goals;

            if ($match->away_goals > $match->home_goals) {
                $stats['won']++;
                $stats['points'] += 3;
            } elseif ($match->away_goals == $match->home_goals) {
                $stats['drawn']++;
                $stats['points'] += 1;
            } else {
                $stats['lost']++;
            }
        }

        $stats['goal_difference'] = $stats['goals_for'] - $stats['goals_against'];

        return $stats;
    }

    private function updatePositions()
    {
        $standings = LeagueStanding::orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        foreach ($standings as $index => $standing) {
            $standing->update(['position' => $index + 1]);
        }
    }


}
