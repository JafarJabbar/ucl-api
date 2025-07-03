<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Game;
use App\Models\LeagueStanding;
use Illuminate\Support\Facades\DB;

class FixtureGeneratorService
{
    /**
     * Generate fixtures for all teams
     * @param int $rounds Number of rounds (1 = single, 2 = double round-robin)
     * @param bool $clearExisting Whether to clear existing fixtures
     * @return array
     */
    public function generateFixtures(int $rounds = 2, bool $clearExisting = true): array
    {
        $teams = Team::all();

        if ($teams->count() < 2) {
            throw new \Exception('At least 2 teams are required to generate fixtures');
        }

        if ($teams->count() % 2 !== 0) {
            throw new \Exception('Even number of teams required for round-robin fixtures');
        }

        try {
            DB::beginTransaction();

            if ($clearExisting) {
                Game::query()->delete();
                // Reset league standings
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
            }

            $fixtures = $this->generateRoundRobinFixtures($teams, $rounds);

            $totalFixtures = 0;
            $currentWeek = 1;

            foreach ($fixtures as $weekFixtures) {
                foreach ($weekFixtures as $fixture) {
                    Game::create([
                        'team_home_id' => $fixture['home_id'],
                        'team_away_id' => $fixture['away_id'],
                        'week' => $currentWeek,
                        'is_finished' => 0,
                        'home_goals' => null,
                        'away_goals' => null,
                        'played_at' => null
                    ]);
                    $totalFixtures++;
                }
                $currentWeek++;
            }

            DB::commit();

            return [
                'total_fixtures' => $totalFixtures,
                'total_weeks' => count($fixtures),
                'teams_count' => $teams->count(),
                'rounds' => $rounds
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Error generating fixtures: ' . $e->getMessage());
        }
    }

    /**
     * Generate round-robin fixtures
     * @param \Illuminate\Database\Eloquent\Collection $teams
     * @param int $rounds
     * @return array
     */
    private function generateRoundRobinFixtures($teams, int $rounds): array
    {
        $teamIds = $teams->pluck('id')->toArray();
        $fixtures = [];

        // Generate single round-robin
        $singleRoundFixtures = $this->generateSingleRoundRobin($teamIds);

        // Add fixtures for each round
        for ($round = 1; $round <= $rounds; $round++) {
            foreach ($singleRoundFixtures as $weekIndex => $weekFixtures) {
                $adjustedWeek = ($round - 1) * count($singleRoundFixtures) + $weekIndex;

                if ($round === 1) {
                    // First round - home/away as generated
                    $fixtures[$adjustedWeek] = $weekFixtures;
                } else {
                    // Subsequent rounds - swap home/away
                    $fixtures[$adjustedWeek] = array_map(function($fixture) {
                        return [
                            'home_id' => $fixture['away_id'],
                            'away_id' => $fixture['home_id']
                        ];
                    }, $weekFixtures);
                }
            }
        }

        return $fixtures;
    }

    /**
     * Generate single round-robin using round-robin algorithm
     * @param array $teamIds
     * @return array
     */
    private function generateSingleRoundRobin(array $teamIds): array
    {
        $teamCount = count($teamIds);
        $fixtures = [];

        // If odd number of teams, add a "bye" team
        if ($teamCount % 2 !== 0) {
            $teamIds[] = null; // null represents bye
            $teamCount++;
        }

        $totalRounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;

        for ($round = 0; $round < $totalRounds; $round++) {
            $weekFixtures = [];

            for ($match = 0; $match < $matchesPerRound; $match++) {
                $home = ($round + $match) % ($teamCount - 1);
                $away = ($teamCount - 1 - $match + $round) % ($teamCount - 1);

                // The last team always plays against the rotating teams
                if ($match === 0) {
                    $away = $teamCount - 1;
                }

                // Skip if either team is null (bye)
                if ($teamIds[$home] === null || $teamIds[$away] === null) {
                    continue;
                }

                $weekFixtures[] = [
                    'home_id' => $teamIds[$home],
                    'away_id' => $teamIds[$away]
                ];
            }

            if (!empty($weekFixtures)) {
                $fixtures[] = $weekFixtures;
            }
        }

        return $fixtures;
    }

    /**
     * Preview fixtures without saving to database
     * @param int $rounds
     * @return array
     */
    public function previewFixtures(int $rounds = 2): array
    {
        $teams = Team::all();

        if ($teams->count() < 2) {
            throw new \Exception('At least 2 teams are required to generate fixtures');
        }

        if ($teams->count() % 2 !== 0) {
            throw new \Exception('Even number of teams required for round-robin fixtures');
        }

        try {
            $fixtures = $this->generateRoundRobinFixtures($teams, $rounds);
            $preview = [];

            foreach ($fixtures as $weekIndex => $weekFixtures) {
                $weekMatches = [];

                foreach ($weekFixtures as $fixture) {
                    $homeTeam = $teams->find($fixture['home_id']);
                    $awayTeam = $teams->find($fixture['away_id']);

                    if ($homeTeam && $awayTeam) {
                        $weekMatches[] = [
                            'home_team' => [
                                'id' => $homeTeam->id,
                                'name' => $homeTeam->name,
                                'short_name' => $homeTeam->short_name
                            ],
                            'away_team' => [
                                'id' => $awayTeam->id,
                                'name' => $awayTeam->name,
                                'short_name' => $awayTeam->short_name
                            ]
                        ];
                    }
                }

                if (!empty($weekMatches)) {
                    $preview[] = [
                        'week' => $weekIndex + 1,
                        'matches' => $weekMatches
                    ];
                }
            }

            return [
                'preview' => $preview,
                'total_weeks' => count($preview),
                'total_matches' => array_sum(array_map(function($week) { return count($week['matches']); }, $preview)),
                'teams_count' => $teams->count(),
                'rounds' => $rounds
            ];

        } catch (\Exception $e) {
            throw new \Exception('Error generating fixture preview: ' . $e->getMessage());
        }
    }

    /**
     * Validate if fixtures can be generated
     * @return array
     */
    public function validateFixtureGeneration(): array
    {
        $teamCount = Team::count();
        $existingGames = Game::count();

        $canGenerate = $teamCount >= 2 && $teamCount % 2 === 0;
        $warnings = [];

        if ($teamCount < 2) {
            $warnings[] = 'At least 2 teams are required';
        }

        if ($teamCount % 2 !== 0) {
            $warnings[] = 'Even number of teams required for round-robin fixtures';
        }

        if ($existingGames > 0) {
            $warnings[] = 'Existing fixtures will be cleared when generating new ones';
        }

        return [
            'can_generate' => $canGenerate,
            'team_count' => $teamCount,
            'existing_games' => $existingGames,
            'warnings' => $warnings
        ];
    }
}
