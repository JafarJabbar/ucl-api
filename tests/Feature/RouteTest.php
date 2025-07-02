<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_standings_route_exists()
    {
        $response = $this->get('/api/v1/standings');
        $response->assertStatus(200);
    }

    public function test_matches_route_exists()
    {
        $response = $this->get('/api/v1/matches');
        $response->assertStatus(200);
    }

    public function test_matches_with_week_route_exists()
    {
        $response = $this->get('/api/v1/matches/1');
        $response->assertStatus(200);
    }

    public function test_play_week_route_exists()
    {
        $response = $this->post('/api/v1/play-week');
        $response->assertStatus(200);
    }

    public function test_play_all_route_exists()
    {
        $response = $this->post('/api/v1/play-all');
        $response->assertStatus(200);
    }

    public function test_predictions_route_exists()
    {
        $response = $this->get('/api/v1/predictions');
        $response->assertStatus(200);
    }

    public function test_update_match_route_accepts_put_requests()
    {
        $response = $this->putJson('/api/v1/matches/1', [
            'home_goals' => 2,
            'away_goals' => 1
        ]);

        $this->assertNotEquals(405, $response->status());
    }

    public function test_reset_match_route_accepts_post_requests()
    {
        $response = $this->post('/api/v1/matches/1/reset');

        $this->assertNotEquals(405, $response->status());
    }
}
