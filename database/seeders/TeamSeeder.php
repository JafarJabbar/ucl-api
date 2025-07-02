<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'strength_rating' => 0.85,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Chelsea-Logo.png'
            ],
            [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'strength_rating' => 0.82,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Arsenal-Logo.png'
            ],
            [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'strength_rating' => 0.90,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Manchester-City-Logo.png'
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'strength_rating' => 0.88,
                'logo_url' => 'https://logos-world.net/wp-content/uploads/2020/06/Liverpool-Logo.png'
            ]
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }
    }
}
