<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MethodController;
use App\Http\Controllers\ContributorController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\SurveyController;

//UserController
Route::post('/user', [UserController::class, 'store']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);
Route::get('/user/{userId}/stats', [UserController::class, 'getStats']);
Route::get('/user/{userId}/sessions', [UserController::class, 'getSessions']);

//SessionController
Route::post('/session/create', [SessionController::class, 'alter']);
Route::post('/session/delete', [SessionController::class, 'delete']);
Route::post('/session/put', [SessionController::class, 'alter']);
Route::post('/session/start', [SessionController::class, 'start']);
Route::post('/session/stop', [SessionController::class, 'stop']);
Route::post('/session/pause', [SessionController::class, 'pause']);
Route::post('/session/resume', [SessionController::class, 'resume']);
Route::post('/session/invite', [SessionController::class, 'invite']);
Route::get('/session/{sessionId}', [SessionController::class, 'get'])
     ->where('sessionId', '[0-9]+');
Route::post('/session/summary/send', [SessionController::class, 'sendSummary']);
Route::get('/session/{sessionId}/summary/download', [SessionController::class, 'downloadSummary']);
Route::get('/session/{sessionId}/summary/download-csv', [SessionController::class, 'downloadCSV']);
Route::post('/session/ice-breaker', [SessionController::class, 'iceBreaker']);
Route::get('/session/{sessionId}/closing', [SessionController::class, 'getClosing']);


//MethodController
Route::get('/methods', [MethodController::class, 'get']);

//RoleController
Route::get('/roles/{sessionId}', [RoleController::class, 'get']);

//ContributorController
Route::post('/contributor/create', [ContributorController::class, 'create']);
Route::get('/contributors/{sessionId}/{userId}', [ContributorController::class, 'get']);
Route::post('/contributor/join', [ContributorController::class, 'join']);
Route::post('/contributor/leave', [ContributorController::class, 'leave']);
Route::post('/contributor/ping', [ContributorController::class, 'ping']);

//IdeaController
Route::post('/idea/store', [IdeaController::class, 'store']);
Route::get('/ideas/{sessionId}', [IdeaController::class, 'get']);
Route::post('/ideas/process', [IdeaController::class, 'process']);

//VoteController
Route::post('/vote/store', [VoteController::class, 'vote']);
Route::get('/votes/{sessionId}', [VoteController::class, 'get']);

// SurveyController
Route::get('/survey/{sessionId}/ideas', [SurveyController::class, 'getTopIdeas']);
Route::get('/survey/{sessionId}/{userId}', [SurveyController::class, 'get']);
Route::post('/survey/store', [SurveyController::class, 'store']);
Route::post('/survey/email/store', [SurveyController::class, 'storeEmail']);
Route::post('/survey/email/verify', [SurveyController::class, 'verifyEmail']);

