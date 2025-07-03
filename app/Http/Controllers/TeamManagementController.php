<?php

namespace App\Http\Controllers;

use App\Services\TeamManagementService;
use App\Services\FixtureGeneratorService;
use App\Traits\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TeamManagementController extends Controller
{
    use Response;

    protected TeamManagementService $teamService;
    protected FixtureGeneratorService $fixtureService;

    public function __construct(TeamManagementService $teamService, FixtureGeneratorService $fixtureService)
    {
        $this->teamService = $teamService;
        $this->fixtureService = $fixtureService;
    }

    /**
     * Import teams from JSON data
     * @param Request $request
     * @return JsonResponse
     */
    public function importTeamsFromJson(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'teams' => 'required|array',
            'teams.*.name' => 'required|string|max:255',
            'teams.*.short_name' => 'required|string|max:3',
            'teams.*.strength_rating' => 'required|numeric|between:0.1,1.0',
            'teams.*.logo_url' => 'nullable|string|url'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $result = $this->teamService->importTeamsFromData($request->teams);

            return $this->success('Teams imported successfully!', [
                'imported_teams' => $result['imported'],
                'skipped_teams' => $result['skipped'],
                'total_teams' => $result['total']
            ]);

        } catch (\Exception $e) {
            Log::error('Error importing teams: ' . $e->getMessage());
            return $this->error('Error importing teams: ' . $e->getMessage());
        }
    }

    /**
     * Add a single team
     * @param Request $request
     * @return JsonResponse
     */
    public function addTeam(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teams,name',
            'short_name' => 'required|string|max:3|unique:teams,short_name',
            'strength_rating' => 'required|numeric|between:0.1,1.0',
            'logo_url' => 'nullable|string|url'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $team = $this->teamService->createTeam($request->all());

            return $this->success('Team added successfully!', $team);

        } catch (\Exception $e) {
            Log::error('Error adding team: ' . $e->getMessage());
            return $this->error('Error adding team: ' . $e->getMessage());
        }
    }

    /**
     * Get all teams
     * @return JsonResponse
     */
    public function getTeams(): JsonResponse
    {
        try {
            $teams = $this->teamService->getAllTeams();
            return $this->success('Teams retrieved successfully', $teams);
        } catch (\Exception $e) {
            Log::error('Error getting teams: ' . $e->getMessage());
            return $this->error('Error retrieving teams');
        }
    }

    /**
     * Delete a team
     * @param $id
     * @return JsonResponse
     */
    public function deleteTeam($id): JsonResponse
    {
        try {
            $this->teamService->deleteTeam((int)$id);
            return $this->success('Team deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error deleting team: ' . $e->getMessage());
            return $this->error('Error deleting team: ' . $e->getMessage());
        }
    }

    /**
     * Generate fixtures for current teams
     * @param Request $request
     * @return JsonResponse
     */
    public function generateFixtures(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rounds' => 'nullable|integer|min:1|max:4',
            'clear_existing' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $rounds = $request->get('rounds', 2); // Default to double round-robin
            $clearExisting = $request->get('clear_existing', true);

            $result = $this->fixtureService->generateFixtures($rounds, $clearExisting);

            return $this->success('Fixtures generated successfully!', [
                'total_fixtures' => $result['total_fixtures'],
                'total_weeks' => $result['total_weeks'],
                'teams_count' => $result['teams_count'],
                'rounds' => $rounds
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating fixtures: ' . $e->getMessage());
            return $this->error('Error generating fixtures: ' . $e->getMessage());
        }
    }

    /**
     * Clear all teams and related data
     * @return JsonResponse
     */
    public function clearAllTeams(): JsonResponse
    {
        try {
            $result = $this->teamService->clearAllTeams();

            return $this->success('All teams and related data cleared successfully!', [
                'teams_deleted' => $result['teams_deleted'],
                'games_deleted' => $result['games_deleted'],
                'standings_deleted' => $result['standings_deleted']
            ]);

        } catch (\Exception $e) {
            Log::error('Error clearing teams: ' . $e->getMessage());
            return $this->error('Error clearing teams: ' . $e->getMessage());
        }
    }

    /**
     * Get fixture generation preview
     * @param Request $request
     * @return JsonResponse
     */
    public function previewFixtures(Request $request): JsonResponse
    {
        try {
            $rounds = $request->get('rounds', 2);
            $preview = $this->fixtureService->previewFixtures($rounds);

            return $this->success('Fixture preview generated', $preview);
        } catch (\Exception $e) {
            Log::error('Error generating fixture preview: ' . $e->getMessage());
            return $this->error('Error generating fixture preview: ' . $e->getMessage());
        }
    }
}
