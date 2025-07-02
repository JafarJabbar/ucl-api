<?php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        $teamNames = [
            'Chelsea' => ['CHE', 0.85, 'https://logos-world.net/wp-content/uploads/2020/06/Chelsea-Logo.png'],
            'Arsenal' => ['ARS', 0.82, 'https://logos-world.net/wp-content/uploads/2020/06/Arsenal-Logo.png'],
            'Manchester City' => ['MCI', 0.90, 'https://logos-world.net/wp-content/uploads/2020/06/Manchester-City-Logo.png'],
            'Liverpool' => ['LIV', 0.88, 'https://logos-world.net/wp-content/uploads/2020/06/Liverpool-Logo.png'],
            'Manchester United' => ['MUN', 0.80, 'https://logos-world.net/wp-content/uploads/2020/06/Manchester-United-Logo.png'],
            'Tottenham' => ['TOT', 0.75, 'https://logos-world.net/wp-content/uploads/2020/06/Tottenham-Logo.png'],
            'Newcastle' => ['NEW', 0.70, 'https://logos-world.net/wp-content/uploads/2020/06/Newcastle-Logo.png'],
            'Aston Villa' => ['AVL', 0.65, 'https://logos-world.net/wp-content/uploads/2020/06/Aston-Villa-Logo.png'],
        ];

        $teamName = $this->faker->randomElement(array_keys($teamNames));
        $teamData = $teamNames[$teamName];

        return [
            'name' => $teamName,
            'short_name' => $teamData[0],
            'strength_rating' => $teamData[1],
            'logo_url' => $teamData[2],
        ];
    }
    public function premierLeagueTeams()
    {
        return $this->state(function (array $attributes) {
            $teams = [
                ['name' => 'Chelsea', 'short_name' => 'CHE', 'strength_rating' => 0.85],
                ['name' => 'Arsenal', 'short_name' => 'ARS', 'strength_rating' => 0.82],
                ['name' => 'Manchester City', 'short_name' => 'MCI', 'strength_rating' => 0.90],
                ['name' => 'Liverpool', 'short_name' => 'LIV', 'strength_rating' => 0.88],
            ];

            static $index = 0;
            $team = $teams[$index % count($teams)];
            $index++;

            return $team;
        });
    }

    public function strong()
    {
        return $this->state(function (array $attributes) {
            return [
                'strength_rating' => $this->faker->randomFloat(2, 0.85, 1.00),
            ];
        });
    }

    public function weak()
    {
        return $this->state(function (array $attributes) {
            return [
                'strength_rating' => $this->faker->randomFloat(2, 0.30, 0.60),
            ];
        });
    }

    public function medium()
    {
        return $this->state(function (array $attributes) {
            return [
                'strength_rating' => $this->faker->randomFloat(2, 0.60, 0.80),
            ];
        });
    }

    public function chelsea()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'strength_rating' => 0.85,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Chelsea-Logo.png',
            ];
        });
    }
    public function arsenal()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'strength_rating' => 0.82,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Arsenal-Logo.png',
            ];
        });
    }

    public function manchesterCity()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'strength_rating' => 0.90,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Manchester-City-Logo.png',
            ];
        });
    }

    public function liverpool()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'strength_rating' => 0.88,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Liverpool-Logo.png',
            ];
        });
    }
}
