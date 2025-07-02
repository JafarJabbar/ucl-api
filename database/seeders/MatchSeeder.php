<?php
// database/seeders/FixedMatchesSeeder.php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;
use App\Models\Team;

class MatchSeeder extends Seeder
{
    public function run()
    {
        Game::truncate();

        $teams = Team::all();
        $teamIds = $teams->pluck('id')->toArray();

        if (count($teamIds) != 4) {
            throw new \Exception('This seeder requires exactly 4 teams');
        }

        echo "Creating fixtures for 4 teams: " . $teams->pluck('name')->implode(', ') . "\n";

        $fixtures = [
            // Round 1: Each team plays once
            ['week' => 1, 'home' => $teamIds[0], 'away' => $teamIds[1]], // Team1 vs Team2
            ['week' => 1, 'home' => $teamIds[2], 'away' => $teamIds[3]], // Team3 vs Team4

            ['week' => 2, 'home' => $teamIds[0], 'away' => $teamIds[2]], // Team1 vs Team3
            ['week' => 2, 'home' => $teamIds[1], 'away' => $teamIds[3]], // Team2 vs Team4

            ['week' => 3, 'home' => $teamIds[0], 'away' => $teamIds[3]], // Team1 vs Team4
            ['week' => 3, 'home' => $teamIds[1], 'away' => $teamIds[2]], // Team2 vs Team3

            // Round 2: Return fixtures (swap home/away)
            ['week' => 4, 'home' => $teamIds[1], 'away' => $teamIds[0]], // Team2 vs Team1
            ['week' => 4, 'home' => $teamIds[3], 'away' => $teamIds[2]], // Team4 vs Team3

            ['week' => 5, 'home' => $teamIds[2], 'away' => $teamIds[0]], // Team3 vs Team1
            ['week' => 5, 'home' => $teamIds[3], 'away' => $teamIds[1]], // Team4 vs Team2

            ['week' => 6, 'home' => $teamIds[3], 'away' => $teamIds[0]], // Team4 vs Team1
            ['week' => 6, 'home' => $teamIds[2], 'away' => $teamIds[1]], // Team3 vs Team2
        ];

        foreach ($fixtures as $fixture) {
            Game::create([
                'team_home_id' => $fixture['home'],
                'team_away_id' => $fixture['away'],
                'week' => $fixture['week'],
                'is_finished' => 0,
                'home_goals' => null,
                'away_goals' => null,
                'played_at' => null
            ]);

            $homeTeam = $teams->find($fixture['home'])->name;
            $awayTeam = $teams->find($fixture['away'])->name;
            echo "Week {$fixture['week']}: {$homeTeam} vs {$awayTeam}\n";
        }

        $this->validateFixtures();
    }

    private function validateFixtures()
    {
        $matches = Game::with(['homeTeam', 'awayTeam'])->get();

        for ($week = 1; $week <= 6; $week++) {
            $weekMatches = $matches->where('week', $week);
            $teamsInWeek = [];

            foreach ($weekMatches as $match) {
                $homeId = $match->team_home_id;
                $awayId = $match->team_away_id;

                if (in_array($homeId, $teamsInWeek) || in_array($awayId, $teamsInWeek)) {
                    throw new \Exception("DUPLICATE TEAM IN WEEK {$week}! Team {$homeId} or {$awayId} appears twice.");
                }

                $teamsInWeek[] = $homeId;
                $teamsInWeek[] = $awayId;
            }

            echo "✅ Week {$week}: " . $weekMatches->count() . " matches, " . count($teamsInWeek) . " team slots\n";
        }

        echo "Fixture validation passed!\n";
    }
}
