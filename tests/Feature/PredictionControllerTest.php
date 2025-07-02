<?php
namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\LeagueStanding;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PredictionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teams = Team::factory()->count(4)->create([
            'strength_rating' => 0.75
        ]);

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

        Game::factory()->count(2)->create([
            'team_home_id' => $this->teams[0]->id,
            'team_away_id' => $this->teams[1]->id,
            'is_finished' => 0,
            'week' => 3
        ]);
    }

    public function test_get_final_predictions_returns_correct_structure()
    {
        $response = $this->getJson('/api/v1/predictions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results' => [
                    '*' => [
                        'team_id',
                        'team_name',
                        'current_points',
                        'projected_points',
                        'championship_probability'
                    ]
                ]
            ]);

        $this->assertFalse($response->json('error'));
        $this->assertCount(4, $response->json('results'));
    }

    public function test_predictions_include_all_teams()
    {
        $response = $this->getJson('/api/v1/predictions');

        $predictions = $response->json('results');
        $teamIds = array_column($predictions, 'team_id');

        foreach ($this->teams as $team) {
            $this->assertContains($team->id, $teamIds);
        }
    }

    public function test_championship_probabilities_are_valid()
    {
        $response = $this->getJson('/api/v1/predictions');

        $predictions = $response->json('results');

        foreach ($predictions as $prediction) {
            $probability = $prediction['championship_probability'];
            $this->assertIsFloat($probability);
            $this->assertGreaterThanOrEqual(0, $probability);
            $this->assertLessThanOrEqual(1, $probability);
        }
    }

    public function test_projected_points_are_greater_than_or_equal_to_current_points()
    {
        $response = $this->getJson('/api/v1/predictions');

        $predictions = $response->json('results');

        foreach ($predictions as $prediction) {
            $this->assertGreaterThanOrEqual(
                $prediction['current_points'],
                $prediction['projected_points'],
                "Projected points should be >= current points for team {$prediction['team_name']}"
            );
        }
    }

    public function test_leading_team_has_highest_championship_probability()
    {
        $response = $this->getJson('/api/v1/predictions');

        $predictions = $response->json('results');

        usort($predictions, function($a, $b) {
            return $b['current_points'] <=> $a['current_points'];
        });

        $leader = $predictions[0];

        foreach ($predictions as $prediction) {
            $this->assertLessThanOrEqual(
                $leader['championship_probability'],
                $prediction['championship_probability']
            );
        }
    }
}

