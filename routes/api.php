<?php

use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\TeamManagementController;
use App\Models\Team;
use App\Services\FixtureGeneratorService;
use Illuminate\Support\Facades\Route;

Route::get('/standings', [LeagueController::class, 'getStandings']);
Route::get('/matches/{week?}', [LeagueController::class, 'getGames']);
Route::post('/play-week', [LeagueController::class, 'playNextWeek']);
Route::post('/play-all', [LeagueController::class, 'playAllRemaining']);
Route::get('/predictions', [PredictionController::class, 'getFinalPredictions']);
Route::put('/matches/{id}', [LeagueController::class, 'updateMatchResult']);
Route::post('/matches/{id}/reset', [LeagueController::class, 'resetMatch']);
Route::post('/reset-all', [LeagueController::class, 'resetAll']);


// Team CRUD
Route::prefix('teams')->group(function () {
    Route::get('/', [TeamManagementController::class, 'getTeams']);
    Route::post('/', [TeamManagementController::class, 'addTeam']);
    Route::post('/import-json', [TeamManagementController::class, 'importTeamsFromJson']);
    Route::delete('/clear-all', [TeamManagementController::class, 'clearAllTeams']);
    Route::delete('/{id}', [TeamManagementController::class, 'deleteTeam']);
});



// Fixtures
Route::prefix('fixtures')->group(function () {
    Route::post('/generate', [TeamManagementController::class, 'generateFixtures']);
    Route::get('/preview', [TeamManagementController::class, 'previewFixtures']);
    Route::get('/validate', [TeamManagementController::class, 'validateFixtures']);
});
