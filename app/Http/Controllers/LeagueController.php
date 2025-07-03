<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\LeagueStanding;
use App\Services\GameSimulator;
use App\Services\LeagueCalculator;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LeagueController extends Controller
{

    use Response;
    protected GameSimulator $gameSimulator;
    protected LeagueCalculator $leagueCalculator;

    public function __construct(GameSimulator $gameSimulator, LeagueCalculator $leagueCalculator)
    {
        $this->gameSimulator = $gameSimulator;
        $this->leagueCalculator = $leagueCalculator;
    }

    /**
     * @return JsonResponse
     */
    public function getStandings(): JsonResponse
    {
        $standings = LeagueStanding::with('team')
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->get();

        return $this->success('List of standings',$standings);
    }

    /**
     * @param $week
     * @return JsonResponse
     */
    public function getGames($week = null): JsonResponse
    {
        $query = Game::with(['homeTeam', 'awayTeam']);

        if ($week) {
            $query->where('week', $week);
        }

        return $this->success('List of games', $query->get());
    }

    /**
     * @return JsonResponse
     */
    public function playNextWeek(): JsonResponse
    {
        $nextWeek = Game::pending()->min('week');

        if (!$nextWeek) {
            return $this->error('No more weeks to play. All matches have been completed!', 400);
        }

        $matches = Game::pending()
            ->where('week', $nextWeek)
            ->get();

        if ($matches->isEmpty()) {
            return $this->error('No matches found for the next week.', 400);
        }

        $simulator = new GameSimulator();
        $calculator = new LeagueCalculator();

        foreach ($matches as $match) {
            $result = $simulator->simulateGame($match->homeTeam, $match->awayTeam);

            $match->update([
                'home_goals' => $result['home_goals'],
                'away_goals' => $result['away_goals'],
                'is_finished' => 1
            ]);
        }

        $calculator->updateStandings();

        return $this->success("Week {$nextWeek} completed successfully!", [
            'week' => $nextWeek,
            'matches_played' => $matches->count()
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function playAllRemaining(): JsonResponse
    {
        $pendingWeeks = Game::pending()
            ->distinct('week')
            ->pluck('week')
            ->sort();

        if ($pendingWeeks->isEmpty()) {
            return $this->error('No remaining matches to play. All matches have been completed!', 400);
        }

        $simulator = new GameSimulator();
        $calculator = new LeagueCalculator();
        $results = [];
        $totalMatchesPlayed = 0;

        foreach ($pendingWeeks as $week) {
            $weekMatches = Game::where('week', $week)
                ->pending()
                ->get();

            $weekResults = [];

            foreach ($weekMatches as $match) {
                $result = $simulator->simulateGame($match->homeTeam, $match->awayTeam);

                $match->update([
                    'home_goals' => $result['home_goals'],
                    'away_goals' => $result['away_goals'],
                    'is_finished' => 1
                ]);

                $weekResults[] = [
                    'match_id' => $match->id,
                    'home_team' => $match->homeTeam->name,
                    'away_team' => $match->awayTeam->name,
                    'result' => $result['home_goals'] . '-' . $result['away_goals']
                ];

                $totalMatchesPlayed++;
            }

            $calculator->updateStandings();
            $results["week_{$week}"] = $weekResults;
        }

        return $this->success("All remaining matches completed! Played {$totalMatchesPlayed} matches across " . count($pendingWeeks) . " weeks.", $results);
    }

    public function updateMatchResult(Request $request, $id)
    {
        $request->validate([
            'home_goals' => 'required|integer|min:0|max:20',
            'away_goals' => 'required|integer|min:0|max:20'
        ]);

        try {
            $match = Game::findOrFail($id);

            $match->update([
                'home_goals' => $request->home_goals,
                'away_goals' => $request->away_goals,
                'is_finished' => 1,
                'played_at' => Carbon::now()
            ]);

            $this->leagueCalculator->updateStandings();

            return $this->success('Match result updated successfully!', [
                'match' => $match->load(['homeTeam', 'awayTeam']),
                'result' => $request->home_goals . '-' . $request->away_goals
            ]);

        } catch (\Exception $e) {
            Log::write('error',
                'Error updating match result: ' . $e->getMessage(),
            );

            return $this->error('Error updating match result');
        }
    }

    public function resetMatch($id)
    {
        try {
            $match = Game::findOrFail($id);

            $match->update([
                'home_goals' => null,
                'away_goals' => null,
                'is_finished' => 0,
                'played_at' => null
            ]);
            $this->leagueCalculator->updateStandings();


            return $this->success('Match reset successfully', [
                'match' => $match->load(['homeTeam', 'awayTeam'])
            ]);

        } catch (\Exception $e) {
            Log::write('error',
                'Error resetting match: ' . $e->getMessage(),
            );
            return $this->error('Error updating match reset');
        }
    }

    /**
     * Reset all matches and standings to initial state
     * @return JsonResponse
     */
    public function resetAll(): JsonResponse
    {
        try {
            Game::query()->update([
                'home_goals' => null,
                'away_goals' => null,
                'is_finished' => 0,
                'played_at' => null
            ]);

            LeagueStanding::query()->update([
                'points' => 0,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'position' => 0
            ]);

            return $this->success('All matches and standings reset successfully!', [
                'matches_reset' => Game::count(),
                'standings_reset' => LeagueStanding::count()
            ]);

        } catch (\Exception $e) {
            Log::write('error',
                'Error resetting all data: ' . $e->getMessage(),
            );

            return $this->error('Error resetting all data');
        }
    }

}
