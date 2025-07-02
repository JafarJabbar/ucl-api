<?php
namespace Tests\Unit\Services;

use App\Models\Team;
use App\Services\GameSimulator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulate_game_returns_valid_result_structure()
    {
        $homeTeam = Team::factory()->create(['strength_rating' => 0.8]);
        $awayTeam = Team::factory()->create(['strength_rating' => 0.6]);

        $simulator = new GameSimulator();
        $result = $simulator->simulateGame($homeTeam, $awayTeam);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('home_goals', $result);
        $this->assertArrayHasKey('away_goals', $result);
        $this->assertIsInt($result['home_goals']);
        $this->assertIsInt($result['away_goals']);
    }

    public function test_simulate_game_returns_realistic_goal_counts()
    {
        $homeTeam = Team::factory()->create(['strength_rating' => 0.8]);
        $awayTeam = Team::factory()->create(['strength_rating' => 0.6]);

        $simulator = new GameSimulator();
        $result = $simulator->simulateGame($homeTeam, $awayTeam);

        $this->assertGreaterThanOrEqual(0, $result['home_goals']);
        $this->assertLessThanOrEqual(6, $result['away_goals']);
        $this->assertGreaterThanOrEqual(0, $result['away_goals']);
        $this->assertLessThanOrEqual(6, $result['home_goals']);
    }

    public function test_stronger_home_team_tends_to_score_more()
    {
        $strongHomeTeam = Team::factory()->create(['strength_rating' => 0.95]);
        $weakAwayTeam = Team::factory()->create(['strength_rating' => 0.3]);

        $simulator = new GameSimulator();

        $totalHomeGoals = 0;
        $totalAwayGoals = 0;
        $simulations = 100;

        for ($i = 0; $i < $simulations; $i++) {
            $result = $simulator->simulateGame($strongHomeTeam, $weakAwayTeam);
            $totalHomeGoals += $result['home_goals'];
            $totalAwayGoals += $result['away_goals'];
        }

        $avgHomeGoals = $totalHomeGoals / $simulations;
        $avgAwayGoals = $totalAwayGoals / $simulations;

        $this->assertGreaterThan($avgAwayGoals, $avgHomeGoals);
    }
}
