<?php

namespace App\Services;

use JetBrains\PhpStorm\ArrayShape;

class GameSimulator
{
    /**
     * @return array{home_goals: mixed, away_goals: mixed}
     */
    #[ArrayShape(['home_goals' => "int", 'away_goals' => "int"])]
    public function simulateGame($homeTeam, $awayTeam): array
    {
        $homeStrength = $homeTeam->strength_rating + 0.1;
        $awayStrength = $awayTeam->strength_rating;

        $homeGoals = $this->generateGoals($homeStrength, $awayStrength);
        $awayGoals = $this->generateGoals($awayStrength, $homeStrength);

        return [
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals
        ];
    }

    /**
     *
     * Get goal counts based on Poisson distribution.
     *
     * @param float $teamStrength
     * @param float $opponentStrength
     * @return int
     */
    private function generateGoals(float $teamStrength, float $opponentStrength): int
    {
        $lambda = $teamStrength / $opponentStrength * 1.5;
        return $this->poissonRandom($lambda);
    }


    /**
     * Generates a random number using a Poisson distribution.
     *
     * @param float $lambda The expected number of goals.
     * @return int The number of goals scored, capped between 0 and 6.
     */
    private function poissonRandom(float $lambda): int
    {
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);


        //Get Goal count
        return max(0, min($k - 1, 6));
    }
}
