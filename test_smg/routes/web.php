<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // ゲーム関連のルート
    Route::get('/game', [GameController::class, 'index'])->name('game.index');
    Route::post('/game/move', [GameController::class, 'move'])->name('game.move');
    Route::post('/game/move-to-next', [GameController::class, 'moveToNext'])->name('game.moveToNext');
    Route::post('/game/reset', [GameController::class, 'reset'])->name('game.reset');
    Route::get('/game/roll-dice', [GameController::class, 'rollDice'])->name('game.rollDice');
    
    // 戦闘関連のルート
    Route::get('/battle', [BattleController::class, 'index'])->name('battle.index');
    Route::post('/battle/start', [BattleController::class, 'startBattle'])->name('battle.start');
    Route::post('/battle/attack', [BattleController::class, 'attack'])->name('battle.attack');
    Route::post('/battle/defend', [BattleController::class, 'defend'])->name('battle.defend');
    Route::post('/battle/escape', [BattleController::class, 'escape'])->name('battle.escape');
    Route::post('/battle/skill', [BattleController::class, 'useSkill'])->name('battle.skill');
    Route::post('/battle/end', [BattleController::class, 'endBattle'])->name('battle.end');
    
    // 同期システム関連のルート
    Route::get('/sync/state', [SyncController::class, 'getGameState'])->name('sync.state');
    Route::post('/sync/device', [SyncController::class, 'syncDeviceState'])->name('sync.device');
    Route::get('/sync/status', [SyncController::class, 'checkSyncStatus'])->name('sync.status');
    
    // 分析システム関連のルート
    Route::get('/analytics/user', [AnalyticsController::class, 'getUserAnalytics'])->name('analytics.user');
    Route::get('/analytics/global', [AnalyticsController::class, 'getGlobalAnalytics'])->name('analytics.global');
    Route::get('/analytics/engagement', [AnalyticsController::class, 'getUserEngagementReport'])->name('analytics.engagement');
    Route::get('/analytics/balance', [AnalyticsController::class, 'getGameBalanceReport'])->name('analytics.balance');
});

require __DIR__.'/auth.php';
