<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MiningController;
use App\Http\Controllers\API\BoostController;
use App\Http\Controllers\API\LeaderboardController;
use App\Http\Controllers\API\WebhookController;

// Ana sayfa
Route::get('/', function () {
    return view('welcome');
});

// API rotaları
Route::prefix('api')->group(function () {
    // Auth rotaları
    Route::post('/auth/telegram', [AuthController::class, 'authenticateWithTelegram']);
    
    // Kullanıcı rotaları
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [UserController::class, 'getUser']);
        Route::put('/user', [UserController::class, 'updateUser']);
        
        // Madencilik rotaları
        Route::post('/mining/start', [MiningController::class, 'startMining']);
        Route::post('/mining/stop', [MiningController::class, 'stopMining']);
        Route::get('/mining/stats', [MiningController::class, 'getMiningStats']);
        
        // Boost rotaları
        Route::get('/boosts', [BoostController::class, 'getBoosts']);
        Route::post('/boosts/purchase', [BoostController::class, 'purchaseBoost']);
        Route::post('/boosts/activate', [BoostController::class, 'activateBoost']);
        
        // Liderlik tablosu rotaları
        Route::get('/leaderboard', [LeaderboardController::class, 'getLeaderboard']);
    });
});

// Webhook rotaları
Route::prefix('webhook')->group(function () {
    Route::post('/telegram/update', [WebhookController::class, 'handleTelegramUpdate']);
    Route::post('/telegram/payment', [WebhookController::class, 'handleTelegramPayment']);
});
