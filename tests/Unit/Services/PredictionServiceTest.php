<?php
namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\Team;
use App\Models\LeagueStanding;
use App\Services\PredictionService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teams = Team::factory()->count(4)->create();

        foreach ($this->teams as $index => $team) {
            LeagueStanding::create([
                'team_id' => $team->id,
                'points' => (4 - $index) * 3,
                'played' => 2,
                'won' => 4 - $index,
                'drawn' => 0,
                'lost' => $index,
                'goals_for' => (4 - $index) * 2,
                'goals_against' => $index,
                'goal_difference' => (4 - $index) * 2 - $index,
                'position' => $index + 1
            ]);
        }
    }

    public function test_predict_final_table_returns_all_teams()
    {
        $predictionService = new PredictionService();
        $predictions = $predictionService->predictFinalTable();

        $this->assertCount(4, $predictions);

        foreach ($predictions as $prediction) {
            $this->assertArrayHasKey('team_id', $prediction);
            $this->assertArrayHasKey('team_name', $prediction);
            $this->assertArrayHasKey('current_points', $prediction);
            $this->assertArrayHasKey('projected_points', $prediction);
            $this->assertArrayHasKey('championship_probability', $prediction);
        }
    }

    public function test_projected_points_are_higher_than_current_points()
    {
        Game::create([
            'team_home_id' => $this->teams[0]->id,
            'team_away_id' => $this->teams[1]->id,
            'week' => 3,
            'is_finished' => 0
        ]);

        $predictionService = new PredictionService();
        $predictions = $predictionService->predictFinalTable();

        foreach ($predictions as $prediction) {
            $this->assertGreaterThanOrEqual(
                $prediction['current_points'],
                $prediction['projected_points']
            );
        }
    }

    public function test_championship_probability_is_between_zero_and_one()
    {
        $predictionService = new PredictionService();
        $predictions = $predictionService->predictFinalTable();

        foreach ($predictions as $prediction) {
            $this->assertGreaterThanOrEqual(0, $prediction['championship_probability']);
            $this->assertLessThanOrEqual(1, $prediction['championship_probability']);
        }
    }

    public function test_leading_team_has_highest_championship_probability()
    {
        $predictionService = new PredictionService();
        $predictions = $predictionService->predictFinalTable();

        usort($predictions, function($a, $b) {
            return $b['current_points'] <=> $a['current_points'];
        });

        $leader = $predictions[0];

        foreach (array_slice($predictions, 1) as $otherTeam) {
            $this->assertGreaterThanOrEqual(
                $otherTeam['championship_probability'],
                $leader['championship_probability']
            );
        }
    }
}
