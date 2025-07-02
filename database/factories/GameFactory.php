<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'team_home_id' => Team::factory(),
            'team_away_id' => Team::factory(),
            'home_goals' => null,
            'away_goals' => null,
            'week' => $this->faker->numberBetween(1, 6),
            'is_finished' => 0,
            'played_at' => null,
        ];
    }

    /**
     * Mark the game as finished with random scores
     */
    public function finished(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'home_goals' => $this->faker->numberBetween(0, 5),
                'away_goals' => $this->faker->numberBetween(0, 5),
                'is_finished' => 1,
                'played_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    /**
     * Create a game for a specific week
     */
    public function week(int $week): static
    {
        return $this->state(function (array $attributes) use ($week) {
            return [
                'week' => $week,
            ];
        });
    }

    /**
     * Create a game with specific teams
     */
    public function teams(int $homeTeamId, int $awayTeamId): static
    {
        return $this->state(function (array $attributes) use ($homeTeamId, $awayTeamId) {
            return [
                'team_home_id' => $homeTeamId,
                'team_away_id' => $awayTeamId,
            ];
        });
    }

    /**
     * Create a high-scoring game
     */
    public function highScoring(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'home_goals' => $this->faker->numberBetween(3, 6),
                'away_goals' => $this->faker->numberBetween(3, 6),
                'is_finished' => 1,
                'played_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    /**
     * Create a draw game
     */
    public function draw(): static
    {
        return $this->state(function (array $attributes) {
            $goals = $this->faker->numberBetween(0, 3);
            return [
                'home_goals' => $goals,
                'away_goals' => $goals,
                'is_finished' => 1,
                'played_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    /**
     * Create a pending game
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'home_goals' => null,
                'away_goals' => null,
                'is_finished' => 0,
                'played_at' => null,
            ];
        });
    }
}
