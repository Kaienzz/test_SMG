<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ItemShopController;
use App\Http\Controllers\BlacksmithController;
use App\Http\Controllers\TavernController;
use App\Http\Controllers\AlchemyShopController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\SkillController;
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
    
    // キャラクター/プレイヤー関連のルート
    Route::get('/player', [PlayerController::class, 'index'])->name('player.index');
    Route::post('/player/heal', [PlayerController::class, 'heal'])->name('player.heal');
    Route::post('/player/restore-mp', [PlayerController::class, 'restoreMp'])->name('player.restoreMp');
    Route::post('/player/restore-sp', [PlayerController::class, 'restoreSp'])->name('player.restoreSp');
    Route::post('/player/gain-experience', [PlayerController::class, 'gainExperience'])->name('player.gainExperience');
    Route::post('/player/take-damage', [PlayerController::class, 'takeDamage'])->name('player.takeDamage');
    Route::post('/player/reset', [PlayerController::class, 'reset'])->name('player.reset');
    Route::get('/player/show', [PlayerController::class, 'show'])->name('player.show');
    Route::post('/player/store', [PlayerController::class, 'store'])->name('player.store');
    Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
    Route::post('/skills/use', [SkillController::class, 'useSkill'])->name('skills.use');
    Route::post('/skills/add-sample', [SkillController::class, 'addSampleSkill'])->name('skills.addSample');
    
    // ゲーム関連のルート
    Route::get('/game', [GameController::class, 'index'])->name('game.index');
    Route::post('/game/move', [GameController::class, 'move'])->name('game.move');
    Route::post('/game/move-to-next', [GameController::class, 'moveToNext'])->name('game.moveToNext');
    Route::post('/game/move-directly', [GameController::class, 'moveDirectly'])->name('game.moveDirectly');
    Route::post('/game/move-to-branch', [GameController::class, 'moveToBranch'])->name('game.moveToBranch');
    Route::post('/game/move-to-direction', [GameController::class, 'moveToDirection'])->name('game.moveToDirection');
    Route::post('/game/reset', [GameController::class, 'reset'])->name('game.reset');
    Route::post('/game/roll-dice', [GameController::class, 'rollDice'])->name('game.rollDice');
    
    // API ルート（町の施設データ取得用）
    Route::get('/api/location/shops', [GameController::class, 'getLocationShops'])->name('api.location.shops');
    
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
    
    // インベントリー関連のルート
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/show', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('/inventory/add-item', [InventoryController::class, 'addItem'])->name('inventory.addItem');
    Route::post('/inventory/remove-item', [InventoryController::class, 'removeItem'])->name('inventory.removeItem');
    Route::post('/inventory/use-item', [InventoryController::class, 'useItem'])->name('inventory.useItem');
    Route::post('/inventory/expand-slots', [InventoryController::class, 'expandSlots'])->name('inventory.expandSlots');
    Route::get('/inventory/items-by-category', [InventoryController::class, 'getItemsByCategory'])->name('inventory.itemsByCategory');
    Route::post('/inventory/add-sample-items', [InventoryController::class, 'addSampleItems'])->name('inventory.addSampleItems');
    Route::post('/inventory/clear', [InventoryController::class, 'clearInventory'])->name('inventory.clearInventory');
    Route::get('/inventory/item-info', [InventoryController::class, 'getItemInfo'])->name('inventory.itemInfo');
    Route::post('/inventory/move-item', [InventoryController::class, 'moveItem'])->name('inventory.moveItem');
    
    // ショップ関連のルート
    Route::prefix('shops')->group(function () {
        Route::get('/item', [ItemShopController::class, 'index'])->name('shops.item.index');
        Route::post('/item/transaction', [ItemShopController::class, 'processTransaction'])->name('shops.item.transaction');
        
        Route::get('/blacksmith', [BlacksmithController::class, 'index'])->name('shops.blacksmith.index');
        Route::post('/blacksmith/transaction', [BlacksmithController::class, 'processTransaction'])->name('shops.blacksmith.transaction');
        
        Route::get('/tavern', [TavernController::class, 'index'])->name('shops.tavern.index');
        Route::post('/tavern/transaction', [TavernController::class, 'processTransaction'])->name('shops.tavern.transaction');
        
        Route::get('/alchemy', [AlchemyShopController::class, 'index'])->name('shops.alchemy.index');
        Route::post('/alchemy/perform', [AlchemyShopController::class, 'performAlchemy'])->name('shops.alchemy.perform');
        Route::post('/alchemy/preview', [AlchemyShopController::class, 'previewAlchemy'])->name('shops.alchemy.preview');
    });
});

require __DIR__.'/auth.php';
