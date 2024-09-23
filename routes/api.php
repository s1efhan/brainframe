<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\IdeaController;
Route::post('/user', [UserController::class, 'store']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/session/delete', [SessionController::class, 'deleteSession']);
Route::post('/session/put', [SessionController::class, 'alterSession']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/method/{methodId}', [MethodController::class, 'getDetails']);
Route::get('/methods', [MethodController::class, 'get']);
Route::post('/session/summary/send', [SessionController::class, 'sendPDF']);
Route::get('/sessions/{sessionId}/roles', [RoleController::class, 'get']);
Route::get('/{userId}/sessions', [SessionController::class, 'getUserSessions']);
Route::post('/contributor', [ContributorController::class, 'create']);
Route::get('contributors/{sessionId}/{userId}', [ContributorController::class, 'get']);
Route::post('/session', [SessionController::class, 'update']);
Route::post('/session/invite', [SessionController::class, 'invite']);
Route::post('/idea', [IdeaController::class, 'store']);
Route::post('/phase', [MethodController::class, 'switchPhase']);
Route::get('/ideas/{sessionId}/{votingPhase}/{contributorId}', [IdeaController::class, 'get']);
Route::post('/collecting/start', [MethodController::class, 'startCollecting']);
Route::post('/collecting/stop', [MethodController::class, 'stopCollecting']);
Route::get('/collecting/timer/{sessionId}', [MethodController::class, 'getCountdown']);
Route::post('/ideas/sendToGPT', [IdeaController::class, 'sendIdeasToGPT']);
Route::post('/vote', [VoteController::class, 'vote']);
Route::get('user/{userId}', [UserController::class, 'get']);
Route::post('/logout', [UserController::class, 'logout']);
Route::post('/ice-breaker', [IdeaController::class, 'iceBreaker']);
Route::get('/{sessionId}/details', [SessionController::class, 'getClosingDetails']);
Route::get('/{sessionId}/pdf', [SessionController::class, 'downloadSessionPDF']);
Route::get('/ideas/6-3-5/{sessionId}/{personalContributorId}/{round}', [IdeaController::class, 'getPassedIdeas']);
Route::post('/session/join', [SessionController::class, 'sessionJoin']);
Route::post('/session/leave', [SessionController::class, 'sessionLeave']);
Route::post('/session/ping', [SessionController::class, 'sessionPing']);
Route::post('/session/vote/update', [SessionController::class, 'votingPhaseUpdate']);
Route::get('/session/{sessionId}', [SessionController::class, 'get'])
     ->where('sessionId', '[0-9]+');

