<?php
namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\LeagueStanding;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teams = Team::factory()->count(4)->create();

        $this->games = collect();
        for ($week = 1; $week <= 2; $week++) {
            $this->games->push(
                Game::factory()->create([
                    'team_home_id' => $this->teams[0]->id,
                    'team_away_id' => $this->teams[1]->id,
                    'week' => $week,
                    'is_finished' => $week === 1 ? 1 : 0,
                    'home_goals' => $week === 1 ? 2 : null,
                    'away_goals' => $week === 1 ? 1 : null,
                ])
            );
        }

        foreach ($this->teams as $index => $team) {
            LeagueStanding::create([
                'team_id' => $team->id,
                'points' => $index === 0 ? 3 : 0,
                'played' => $index === 0 || $index === 1 ? 1 : 0,
                'won' => $index === 0 ? 1 : 0,
                'drawn' => 0,
                'lost' => $index === 1 ? 1 : 0,
                'goals_for' => $index === 0 ? 2 : ($index === 1 ? 1 : 0),
                'goals_against' => $index === 0 ? 1 : ($index === 1 ? 2 : 0),
                'goal_difference' => $index === 0 ? 1 : ($index === 1 ? -1 : 0),
                'position' => $index + 1
            ]);
        }
    }

    public function test_get_standings_returns_correct_data()
    {
        $response = $this->getJson('/api/v1/standings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results' => [
                    '*' => [
                        'id',
                        'team_id',
                        'points',
                        'played',
                        'won',
                        'drawn',
                        'lost',
                        'goals_for',
                        'goals_against',
                        'goal_difference',
                        'position',
                        'team' => [
                            'id',
                            'name',
                            'strength_rating',
                            'logo_url'
                        ]
                    ]
                ]
            ]);

        $this->assertFalse($response->json('error'));
        $this->assertCount(4, $response->json('results'));
    }

    public function test_get_standings_ordered_by_points_and_goal_difference()
    {
        $response = $this->getJson('/api/v1/standings');

        $standings = $response->json('results');

        $this->assertEquals(3, $standings[0]['points']);
        $this->assertEquals(0, $standings[1]['points']);

        for ($i = 0; $i < count($standings) - 1; $i++) {
            $current = $standings[$i];
            $next = $standings[$i + 1];

            $this->assertTrue(
                $current['points'] > $next['points'] ||
                ($current['points'] == $next['points'] && $current['goal_difference'] >= $next['goal_difference'])
            );
        }
    }

    public function test_get_games_returns_all_games_when_no_week_specified()
    {
        $response = $this->getJson('/api/v1/matches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results' => [
                    '*' => [
                        'id',
                        'team_home_id',
                        'team_away_id',
                        'home_goals',
                        'away_goals',
                        'week',
                        'is_finished',
                        'home_team' => ['id', 'name'],
                        'away_team' => ['id', 'name']
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('results'));
    }

    public function test_get_games_filters_by_week_when_specified()
    {
        $response = $this->getJson('/api/v1/matches/1');

        $response->assertStatus(200);

        $games = $response->json('results');
        $this->assertCount(1, $games);
        $this->assertEquals(1, $games[0]['week']);
    }

    public function test_play_next_week_simulates_pending_matches()
    {
        $this->assertEquals(0, $this->games->where('week', 2)->first()->is_finished);

        $response = $this->postJson('/api/v1/play-week');

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
                'message' => 'Week completed'
            ]);

        $this->assertDatabaseHas('games', [
            'week' => 2,
            'is_finished' => 1
        ]);

        $updatedGame = Game::where('week', 2)->first();
        $this->assertNotNull($updatedGame->home_goals);
        $this->assertNotNull($updatedGame->away_goals);
    }

    public function test_play_all_remaining_completes_all_pending_matches()
    {
        Game::factory()->count(3)->create([
            'team_home_id' => $this->teams[2]->id,
            'team_away_id' => $this->teams[3]->id,
            'week' => 3,
            'is_finished' => 0
        ]);

        $pendingCount = Game::where('is_finished', 0)->count();
        $this->assertGreaterThan(0, $pendingCount);

        $response = $this->postJson('/api/v1/play-all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results'
            ]);

        $this->assertEquals(0, Game::where('is_finished', 0)->count());
    }

    public function test_update_match_result_updates_game_and_standings()
    {
        $game = $this->games->first();

        $response = $this->putJson("/api/v1/matches/{$game->id}", [
            'home_goals' => 3,
            'away_goals' => 0
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results' => [
                    'match',
                    'result'
                ]
            ]);

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'home_goals' => 3,
            'away_goals' => 0,
            'is_finished' => 1
        ]);

        $this->assertEquals('3-0', $response->json('results.result'));
    }

    public function test_update_match_result_validates_input()
    {
        $game = $this->games->first();

        $response = $this->putJson("/api/v1/matches/{$game->id}", [
            'home_goals' => -1,
            'away_goals' => 25
        ]);

        $response->assertStatus(422); // Validation error
    }

    public function test_update_match_result_requires_valid_fields()
    {
        $game = $this->games->first();

        $response = $this->putJson("/api/v1/matches/{$game->id}", [
            'home_goals' => 2
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_match_clears_result_and_updates_standings()
    {
        $game = $this->games->where('is_finished', 1)->first();

        $response = $this->postJson("/api/v1/matches/{$game->id}/reset");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'error',
                'results' => [
                    'match'
                ]
            ]);

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'home_goals' => null,
            'away_goals' => null,
            'is_finished' => 0,
            'played_at' => null
        ]);
    }

    public function test_update_match_result_returns_404_for_invalid_match()
    {
        $response = $this->putJson('/api/v1/matches/999', [
            'home_goals' => 2,
            'away_goals' => 1
        ]);

        $response->assertStatus(404);
    }

    public function test_reset_match_returns_404_for_invalid_match()
    {
        $response = $this->postJson('/api/v1/matches/999/reset');

        $response->assertStatus(404);
    }
}
