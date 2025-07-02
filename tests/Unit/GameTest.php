<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Team;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_can_be_created()
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = Game::create([
            'team_home_id' => $homeTeam->id,
            'team_away_id' => $awayTeam->id,
            'week' => 1,
            'is_finished' => 0
        ]);

        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'team_home_id' => $homeTeam->id,
            'team_away_id' => $awayTeam->id,
            'week' => 1,
            'is_finished' => 0
        ]);
    }

    public function test_game_result_attribute_returns_vs_when_not_finished()
    {
        $game = Game::factory()->create([
            'is_finished' => 0,
            'home_goals' => null,
            'away_goals' => null
        ]);

        $this->assertEquals('vs', $game->getResultAttribute());
    }

    public function test_game_result_attribute_returns_score_when_finished()
    {
        $game = Game::factory()->create([
            'is_finished' => 1,
            'home_goals' => 2,
            'away_goals' => 1
        ]);

        $this->assertEquals('2-1', $game->getResultAttribute());
    }

    public function test_get_result_for_team_returns_win_for_winning_team()
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = Game::factory()->create([
            'team_home_id' => $homeTeam->id,
            'team_away_id' => $awayTeam->id,
            'is_finished' => 1,
            'home_goals' => 3,
            'away_goals' => 1
        ]);

        $this->assertEquals('win', $game->getResultForTeam($homeTeam->id));
        $this->assertEquals('loss', $game->getResultForTeam($awayTeam->id));
    }

    public function test_get_result_for_team_returns_draw_when_scores_equal()
    {
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = Game::factory()->create([
            'team_home_id' => $homeTeam->id,
            'team_away_id' => $awayTeam->id,
            'is_finished' => 1,
            'home_goals' => 2,
            'away_goals' => 2
        ]);

        $this->assertEquals('draw', $game->getResultForTeam($homeTeam->id));
        $this->assertEquals('draw', $game->getResultForTeam($awayTeam->id));
    }

    public function test_finished_scope_returns_only_finished_games()
    {
        Game::factory()->count(3)->create(['is_finished' => 1]);
        Game::factory()->count(2)->create(['is_finished' => 0]);

        $finishedGames = Game::finished()->get();

        $this->assertCount(3, $finishedGames);
        $finishedGames->each(function ($game) {
            $this->assertEquals(1, $game->is_finished);
        });
    }

    public function test_pending_scope_returns_only_pending_games()
    {
        Game::factory()->count(3)->create(['is_finished' => 1]);
        Game::factory()->count(2)->create(['is_finished' => 0]);

        $pendingGames = Game::pending()->get();

        $this->assertCount(2, $pendingGames);
        $pendingGames->each(function ($game) {
            $this->assertEquals(0, $game->is_finished);
        });
    }
}
