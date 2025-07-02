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
        $simulator = new GameSimulator();
        $calculator = new LeagueCalculator();

        $nextWeek = Game::pending()
            ->min('week');


        $matches = Game::pending()
            ->where('week', $nextWeek)
            ->get();

        foreach ($matches as $match) {
            $result = $simulator->simulateGame($match->homeTeam, $match->awayTeam);

            $match->update([
                'home_goals' => $result['home_goals'],
                'away_goals' => $result['away_goals'],
                'is_finished' => 1
            ]);
        }

        $calculator->updateStandings();

        return $this->success('Week completed', []);
    }

    /**
     * @return JsonResponse
     */
    public function playAllRemaining(): JsonResponse
    {
        $simulator = new GameSimulator();
        $calculator = new LeagueCalculator();

        $pendingWeeks = Game::pending()
            ->distinct('week')
            ->pluck('week')
            ->sort();

        $results = [];

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
            }

            $calculator->updateStandings();
            $results[$week] = $weekResults;
        }

        return $this->success('List of standings', $results);
    }

    public function updateMatchResult(Request $request, $id)
    {
        $request->validate([
            'home_goals' => 'required|integer|min:0|max:20',
            'away_goals' => 'required|integer|min:0|max:20'
        ]);

        try {
            $match = Game::find($id);
            if (!$match) return $this->error('Match not found', 404);

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
            $match = Game::find($id);


            if (!$match) {
                return $this->error('Match not found', 404);
            }
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

}
