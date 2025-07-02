<?php
namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\Team;
use App\Models\LeagueStanding;
use App\Services\LeagueCalculator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LeagueCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team1 = Team::factory()->create(['name' => 'Team A']);
        $this->team2 = Team::factory()->create(['name' => 'Team B']);
        $this->team3 = Team::factory()->create(['name' => 'Team C']);
        $this->team4 = Team::factory()->create(['name' => 'Team D']);
    }

    public function test_update_standings_creates_standings_for_all_teams()
    {
        $calculator = new LeagueCalculator();

        ob_start();
        $calculator->updateStandings();
        ob_end_clean();

        $this->assertEquals(4, LeagueStanding::count());

        $this->assertDatabaseHas('league_standings', ['team_id' => $this->team1->id]);
        $this->assertDatabaseHas('league_standings', ['team_id' => $this->team2->id]);
        $this->assertDatabaseHas('league_standings', ['team_id' => $this->team3->id]);
        $this->assertDatabaseHas('league_standings', ['team_id' => $this->team4->id]);
    }

    public function test_standings_calculation_with_completed_matches()
    {
        Game::create([
            'team_home_id' => $this->team1->id,
            'team_away_id' => $this->team2->id,
            'home_goals' => 3,
            'away_goals' => 1,
            'week' => 1,
            'is_finished' => 1
        ]);

        Game::create([
            'team_home_id' => $this->team3->id,
            'team_away_id' => $this->team4->id,
            'home_goals' => 2,
            'away_goals' => 2,
            'week' => 1,
            'is_finished' => 1
        ]);

        $calculator = new LeagueCalculator();

        ob_start();
        $calculator->updateStandings();
        ob_end_clean();

        $team1Standing = LeagueStanding::where('team_id', $this->team1->id)->first();
        $this->assertEquals(3, $team1Standing->points);
        $this->assertEquals(1, $team1Standing->played);
        $this->assertEquals(1, $team1Standing->won);
        $this->assertEquals(0, $team1Standing->drawn);
        $this->assertEquals(0, $team1Standing->lost);
        $this->assertEquals(3, $team1Standing->goals_for);
        $this->assertEquals(1, $team1Standing->goals_against);
        $this->assertEquals(2, $team1Standing->goal_difference);

        $team2Standing = LeagueStanding::where('team_id', $this->team2->id)->first();
        $this->assertEquals(0, $team2Standing->points);
        $this->assertEquals(1, $team2Standing->played);
        $this->assertEquals(0, $team2Standing->won);
        $this->assertEquals(0, $team2Standing->drawn);
        $this->assertEquals(1, $team2Standing->lost);

        $team3Standing = LeagueStanding::where('team_id', $this->team3->id)->first();
        $this->assertEquals(1, $team3Standing->points);
        $this->assertEquals(1, $team3Standing->played);
        $this->assertEquals(0, $team3Standing->won);
        $this->assertEquals(1, $team3Standing->drawn);
        $this->assertEquals(0, $team3Standing->lost);
    }

    public function test_positions_are_updated_correctly()
    {
        Game::create([
            'team_home_id' => $this->team1->id,
            'team_away_id' => $this->team2->id,
            'home_goals' => 3,
            'away_goals' => 0,
            'week' => 1,
            'is_finished' => 1
        ]);

        Game::create([
            'team_home_id' => $this->team3->id,
            'team_away_id' => $this->team4->id,
            'home_goals' => 1,
            'away_goals' => 1,
            'week' => 1,
            'is_finished' => 1
        ]);

        $calculator = new LeagueCalculator();

        ob_start();
        $calculator->updateStandings();
        ob_end_clean();

        $standings = LeagueStanding::orderBy('position')->get();

        $this->assertEquals(1, $standings->where('team_id', $this->team1->id)->first()->position);

        $team3Position = $standings->where('team_id', $this->team3->id)->first()->position;
        $team4Position = $standings->where('team_id', $this->team4->id)->first()->position;
        $this->assertContains($team3Position, [2, 3]);
        $this->assertContains($team4Position, [2, 3]);
        $this->assertEquals(4, $standings->where('team_id', $this->team2->id)->first()->position);
    }
}
