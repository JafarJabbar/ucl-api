<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Services\GameSimulator;
use Tests\TestCase;

class GameSimulatorTest extends TestCase
{
    public function test_match_simulation_returns_valid_scores() {
        $homeTeam = Team::factory()->create(['strength_rating' => 0.8]);
        $awayTeam = Team::factory()->create(['strength_rating' => 0.6]);

        $simulator = new GameSimulator();
        $result = $simulator->simulateGame($homeTeam, $awayTeam);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('home_goals', $result);
        $this->assertArrayHasKey('away_goals', $result);
        $this->assertGreaterThanOrEqual(0, $result['home_goals']);
        $this->assertGreaterThanOrEqual(0, $result['away_goals']);
    }
}
