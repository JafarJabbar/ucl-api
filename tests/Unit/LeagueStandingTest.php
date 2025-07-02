<?php

namespace Tests\Unit;

use App\Models\LeagueStanding;
use App\Models\Team;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueStandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_league_standing_can_be_created()
    {
        $team = Team::factory()->create();

        $standing = LeagueStanding::create([
            'team_id' => $team->id,
            'points' => 9,
            'played' => 3,
            'won' => 3,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 6,
            'goals_against' => 2,
            'goal_difference' => 4,
            'position' => 1
        ]);

        $this->assertDatabaseHas('league_standings', [
            'team_id' => $team->id,
            'points' => 9,
            'played' => 3,
            'won' => 3
        ]);
    }

    public function test_win_percentage_calculation()
    {
        $standing = new LeagueStanding([
            'played' => 10,
            'won' => 7,
            'drawn' => 2,
            'lost' => 1
        ]);

        $this->assertEquals(70.0, $standing->getWinPercentageAttribute());
    }

    public function test_loss_percentage_calculation()
    {
        $standing = new LeagueStanding([
            'played' => 10,
            'won' => 7,
            'drawn' => 2,
            'lost' => 1
        ]);

        $this->assertEquals(10.0, $standing->getLossPercentageAttribute());
    }

    public function test_points_per_game_calculation()
    {
        $standing = new LeagueStanding([
            'played' => 4,
            'points' => 10
        ]);

        $this->assertEquals(2.5, $standing->getPointsPerGameAttribute());
    }

    public function test_percentages_return_zero_when_no_games_played()
    {
        $standing = new LeagueStanding([
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'points' => 0
        ]);

        $this->assertEquals(0, $standing->getWinPercentageAttribute());
        $this->assertEquals(0, $standing->getLossPercentageAttribute());
        $this->assertEquals(0, $standing->getPointsPerGameAttribute());
    }
}
