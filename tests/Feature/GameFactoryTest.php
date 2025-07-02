<?php
namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_factory_creates_valid_game()
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = Game::factory()->create([
            'team_home_id' => $homeTeam->id,
            'team_away_id' => $awayTeam->id,
        ]);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals($homeTeam->id, $game->team_home_id);
        $this->assertEquals($awayTeam->id, $game->team_away_id);
        $this->assertIsInt($game->week);
        $this->assertContains($game->is_finished, [0, 1]);
    }

    public function test_finished_game_factory_state()
    {
        $game = Game::factory()->finished()->create();

        $this->assertEquals(1, $game->is_finished);
        $this->assertNotNull($game->home_goals);
        $this->assertNotNull($game->away_goals);
        $this->assertNotNull($game->played_at);
        $this->assertIsInt($game->home_goals);
        $this->assertIsInt($game->away_goals);
        $this->assertGreaterThanOrEqual(0, $game->home_goals);
        $this->assertLessThanOrEqual(5, $game->home_goals);
    }

    public function test_pending_game_factory_state()
    {
        $game = Game::factory()->pending()->create();

        $this->assertEquals(0, $game->is_finished);
        $this->assertNull($game->home_goals);
        $this->assertNull($game->away_goals);
        $this->assertNull($game->played_at);
    }

    public function test_week_specific_game_factory()
    {
        $game = Game::factory()->week(3)->create();

        $this->assertEquals(3, $game->week);
    }

    public function test_teams_specific_game_factory()
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = Game::factory()->teams($homeTeam->id, $awayTeam->id)->create();

        $this->assertEquals($homeTeam->id, $game->team_home_id);
        $this->assertEquals($awayTeam->id, $game->team_away_id);
    }

    public function test_high_scoring_game_factory()
    {
        $game = Game::factory()->highScoring()->create();

        $this->assertEquals(1, $game->is_finished);
        $this->assertGreaterThanOrEqual(3, $game->home_goals);
        $this->assertGreaterThanOrEqual(3, $game->away_goals);
        $this->assertLessThanOrEqual(6, $game->home_goals);
        $this->assertLessThanOrEqual(6, $game->away_goals);
    }

    public function test_draw_game_factory()
    {
        $game = Game::factory()->draw()->create();

        $this->assertEquals(1, $game->is_finished);
        $this->assertEquals($game->home_goals, $game->away_goals);
        $this->assertGreaterThanOrEqual(0, $game->home_goals);
        $this->assertLessThanOrEqual(3, $game->home_goals);
    }
}
