<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Team;
use App\Models\Game;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_teams()
    {
        $this->artisan('db:seed');

        $this->assertEquals(4, Team::count());

        $expectedTeams = ['Chelsea', 'Arsenal', 'Manchester City', 'Liverpool'];
        foreach ($expectedTeams as $teamName) {
            $this->assertDatabaseHas('teams', ['name' => $teamName]);
        }
    }

    public function test_database_seeder_creates_complete_fixture_list()
    {
        $this->artisan('db:seed');

        $this->assertEquals(12, Game::count());

        $weeks = Game::distinct('week')->pluck('week')->sort()->values();
        $this->assertEquals([1, 2, 3, 4, 5, 6], $weeks->toArray());

        for ($week = 1; $week <= 6; $week++) {
            $weekMatches = Game::where('week', $week)->count();
            $this->assertEquals(2, $weekMatches, "Week {$week} should have 2 matches");
        }
    }

    public function test_seeded_teams_have_correct_strength_ratings()
    {
        $this->artisan('db:seed');

        $expectedRatings = [
            'Chelsea' => 0.85,
            'Arsenal' => 0.82,
            'Manchester City' => 0.90,
            'Liverpool' => 0.88
        ];

        foreach ($expectedRatings as $teamName => $expectedRating) {
            $team = Team::where('name', $teamName)->first();
            $this->assertNotNull($team);
            $this->assertEquals($expectedRating, $team->strength_rating);
        }
    }

    public function test_seeded_games_are_initially_unfinished()
    {
        $this->artisan('db:seed');

        $finishedGames = Game::where('is_finished', 1)->count();
        $this->assertEquals(0, $finishedGames, 'All seeded games should be unfinished initially');

        $pendingGames = Game::where('is_finished', 0)->count();
        $this->assertEquals(12, $pendingGames, 'All 12 games should be pending');
    }
}
