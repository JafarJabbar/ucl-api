<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_factory_creates_team()
    {
        $team = Team::factory()->create();

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => $team->name,
        ]);
    }

    public function test_chelsea_factory_creates_correct_team()
    {
        $team = Team::factory()->chelsea()->create();

        $this->assertEquals('Chelsea', $team->name);
        $this->assertEquals('CHE', $team->short_name);
        $this->assertEquals(0.85, $team->strength_rating);
    }

    public function test_strong_team_has_high_rating()
    {
        $team = Team::factory()->strong()->create();

        $this->assertGreaterThanOrEqual(0.85, $team->strength_rating);
        $this->assertLessThanOrEqual(1.00, $team->strength_rating);
    }

    public function test_weak_team_has_low_rating()
    {
        $team = Team::factory()->weak()->create();

        $this->assertGreaterThanOrEqual(0.30, $team->strength_rating);
        $this->assertLessThanOrEqual(0.60, $team->strength_rating);
    }

    public function test_can_create_multiple_teams()
    {
        $teams = Team::factory()->count(4)->create();

        $this->assertCount(4, $teams);
        $this->assertEquals(4, Team::count());
    }
}
