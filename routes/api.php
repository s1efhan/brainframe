<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\IdeaController;
Route::post('/user', [UserController::class, 'store']);
Route::get('/method/{methodId}', [MethodController::class, 'getDetails']);
Route::get('/methods', [MethodController::class, 'get']);
Route::get('/sessions/{sessionId}/roles', [RoleController::class, 'get']);
Route::post('/contributor', [ContributorController::class, 'create']);
Route::get('contributors/{sessionId}/{userId}', [ContributorController::class, 'get']);
Route::post('/session', [SessionController::class, 'update']);
Route::post('/session/invite', [SessionController::class, 'invite']);
Route::post('/idea', [IdeaController::class, 'store']);
Route::get('/session/{sessionId}', [SessionController::class, 'get']);
Route::post('/phase', [MethodController::class, 'switchPhase']);
Route::get('/ideas/{sessionId}/{votingPhaseNumber}', [IdeaController::class, 'get']);