<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Game;
use App\Models\LeagueStanding;
use Exception;
use Illuminate\Support\Facades\DB;

class TeamManagementService
{
    /**
     * Import teams from JSON data array
     * @param array $teamsData
     * @return array
     * @throws Exception
     */
    public function importTeamsFromData(array $teamsData): array
    {
        $imported = 0;
        $skipped = 0;
        $importedTeams = [];

        DB::beginTransaction();

        try {
            foreach ($teamsData as $teamData) {
                $existingTeam = Team::where('name', $teamData['name'])
                    ->orWhere('short_name', $teamData['short_name'])
                    ->first();

                if ($existingTeam) {
                    $skipped++;
                    continue;
                }

                $team = Team::create([
                    'name' => $teamData['name'],
                    'short_name' => $teamData['short_name'],
                    'strength_rating' => $teamData['strength_rating'],
                    'logo_url' => $teamData['logo_url'] ?? null
                ]);

                LeagueStanding::create([
                    'team_id' => $team->id,
                    'points' => 0,
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_difference' => 0,
                    'position' => 0
                ]);

                $importedTeams[] = $team;
                $imported++;
            }

            DB::commit();

            return [
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => count($teamsData),
                'teams' => $importedTeams
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a single team
     * @param array $teamData
     * @return Team
     * @throws Exception
     */
    public function createTeam(array $teamData): Team
    {
        DB::beginTransaction();

        try {
            $team = Team::create($teamData);

            // Create initial standing record
            LeagueStanding::create([
                'team_id' => $team->id,
                'points' => 0,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'position' => 0
            ]);

            DB::commit();
            return $team;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all teams
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllTeams()
    {
        return Team::orderBy('name')->get();
    }

    /**
     * Delete a team and its related data
     * @param int $teamId
     * @return void
     * @throws Exception
     */
    public function deleteTeam(int $teamId): void
    {
        DB::beginTransaction();

        try {
            $team = Team::findOrFail($teamId);

            Game::where('team_home_id', $teamId)
                ->orWhere('team_away_id', $teamId)
                ->delete();
            LeagueStanding::where('team_id', $teamId)->delete();
            $team->delete();

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clear all teams and related data
     * @return array
     * @throws Exception
     */
    public function clearAllTeams(): array
    {

        try {
            $teamsCount = Team::count();
            $gamesCount = Game::count();
            $standingsCount = LeagueStanding::count();

            Game::truncate();
            LeagueStanding::truncate();
            Team::truncate();


            return [
                'teams_deleted' => $teamsCount,
                'games_deleted' => $gamesCount,
                'standings_deleted' => $standingsCount
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Validate teams for fixture generation
     * @return bool
     */
    public function validateTeamsForFixtures(): bool
    {
        $teamCount = Team::count();
        return $teamCount >= 2 && $teamCount % 2 === 0;
    }

    /**
     * Get team count
     * @return int
     */
    public function getTeamCount(): int
    {
        return Team::count();
    }
}
