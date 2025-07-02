<?php

use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PredictionController;
use Illuminate\Support\Facades\Route;

Route::get('/standings', [LeagueController::class, 'getStandings']);
Route::get('/matches/{week?}', [LeagueController::class, 'getGames']);
Route::post('/play-week', [LeagueController::class, 'playNextWeek']);
Route::post('/play-all', [LeagueController::class, 'playAllRemaining']);
Route::get('/predictions', [PredictionController::class, 'getFinalPredictions']);
Route::put('/matches/{id}', [LeagueController::class, 'updateMatchResult']);
Route::post('/matches/{id}/reset', [LeagueController::class, 'resetMatch']);
