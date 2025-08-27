<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ItemFacilityController;
use App\Http\Controllers\BlacksmithFacilityController;
use App\Http\Controllers\TavernFacilityController;
use App\Http\Controllers\AlchemyFacilityController;
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

// Layout optimization demo routes
Route::get('/layout', function () {
    return redirect('/layout_town');
})->name('layout.demo');

// Town layout demo
Route::get('/layout_town', function () {
    $mockPlayer = (object) [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 100,
        'max_hp' => 100,
        'mp' => 20,
        'max_mp' => 20,
        'sp' => 30,
        'max_sp' => 30,
        'location_type' => 'town',
        'location_id' => 'town_a',
        'game_position' => 0,
        'current_location_type' => 'town',
        'attack' => 10,
        'defense' => 8,
        'agility' => 12,
        'evasion' => 15,
        'magic_attack' => 8,
        'accuracy' => 85,
        'gold' => 1000,
        'experience' => 0
    ];
    
    $mockCurrentLocation = (object) [
        'id' => 'town_a',
        'name' => 'プリマ町',
        'type' => 'town'
    ];
    
    return view('game-3column', [
        'gameState' => 'town',
        'player' => $mockPlayer,
        'character' => $mockPlayer,
        'currentLocation' => $mockCurrentLocation,
        'nextLocation' => null,
        'movementInfo' => []
    ]);
})->name('layout.town');

// Road layout demo  
Route::get('/layout_road', function () {
    $mockPlayer = (object) [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 100,
        'max_hp' => 100,
        'mp' => 20,
        'max_mp' => 20,
        'sp' => 30,
        'max_sp' => 30,
        'location_type' => 'road',
        'location_id' => 'road_a',
        'game_position' => 35,
        'current_location_type' => 'road',
        'attack' => 10,
        'defense' => 8,
        'agility' => 12,
        'evasion' => 15,
        'magic_attack' => 8,
        'accuracy' => 85,
        'gold' => 1000,
        'experience' => 0
    ];
    
    $mockCurrentLocation = (object) [
        'id' => 'road_a',
        'name' => 'プリマ街道',
        'type' => 'road'
    ];
    
    $mockNextLocation = (object) [
        'id' => 'town_b',
        'name' => 'セカンダ町',
        'type' => 'town'
    ];
    
    $mockMovementInfo = [
        'total_dice_count' => 2,
        'base_dice_count' => 1,
        'extra_dice' => 1,
        'dice_bonus' => 2,
        'movement_multiplier' => 1.0,
        'min_possible_movement' => 3,
        'max_possible_movement' => 14
    ];
    
    return view('game-3column', [
        'gameState' => 'road',
        'player' => $mockPlayer,
        'character' => $mockPlayer,
        'currentLocation' => $mockCurrentLocation,
        'nextLocation' => $mockNextLocation,
        'movementInfo' => $mockMovementInfo
    ]);
})->name('layout.road');

// Battle layout demo
Route::get('/layout_fight', function () {
    $mockCharacter = [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 85,
        'max_hp' => 100,
        'mp' => 15,
        'max_mp' => 20,
        'sp' => 25,
        'max_sp' => 30,
        'attack' => 10,
        'magic_attack' => 8,
        'defense' => 8,
        'agility' => 12,
        'accuracy' => 85,
        'evasion' => 15,
        'gold' => 1000,
        'experience' => 0
    ];
    
    $mockMonster = [
        'name' => 'スライム',
        'emoji' => '🟢',
        'description' => '森に住むスライムの一種。',
        'stats' => [
            'hp' => 15,
            'max_hp' => 20,
            'attack' => 5,
            'defense' => 3,
            'agility' => 8,
            'evasion' => 10
        ]
    ];
    
    $mockBattle = [
        'turn' => 3,
        'phase' => 'player',
        'battle_log' => [
            ['action' => 'battle_start', 'message' => 'フォレストスライムが現れた！'],
            ['action' => 'player_attack', 'message' => 'テストプレイヤーの攻撃！ 18ダメージ！'],
            ['action' => 'monster_attack', 'message' => 'フォレストスライムの攻撃！ 8ダメージを受けた！']
        ]
    ];
    
    return view('game-3column', [
        'gameState' => 'battle',
        'player' => (object)$mockCharacter,
        'character' => $mockCharacter,
        'monster' => $mockMonster,
        'battle' => $mockBattle,
        'currentLocation' => null,
        'nextLocation' => null,
        'movementInfo' => []
    ]);
})->name('layout.battle');

// No-right versions (2-column layout)
Route::get('/layout_town_noright', function () {
    $mockPlayer = (object) [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 100,
        'max_hp' => 100,
        'mp' => 20,
        'max_mp' => 20,
        'sp' => 30,
        'max_sp' => 30,
        'location_type' => 'town',
        'location_id' => 'town_a',
        'current_location_type' => 'town',
        'attack' => 10,
        'defense' => 8,
        'agility' => 12,
        'evasion' => 15,
        'magic_attack' => 8,
        'accuracy' => 85,
        'gold' => 1000,
        'experience' => 0
    ];
    
    $mockCurrentLocation = (object) [
        'id' => 'town_a',
        'name' => 'プリマ町',
        'type' => 'town'
    ];
    
    return view('game', [
        'gameState' => 'town',
        'player' => $mockPlayer,
        'character' => $mockPlayer,
        'currentLocation' => $mockCurrentLocation,
        'nextLocation' => null,
        'movementInfo' => []
    ]);
})->name('layout.town.noright');

Route::get('/layout_road_noright', function () {
    $mockPlayer = (object) [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 100,
        'max_hp' => 100,
        'mp' => 20,
        'max_mp' => 20,
        'sp' => 30,
        'max_sp' => 30,
        'location_type' => 'road',
        'location_id' => 'road_a',
        'game_position' => 35,
        'current_location_type' => 'road',
        'attack' => 10,
        'defense' => 8,
        'agility' => 12,
        'evasion' => 15,
        'magic_attack' => 8,
        'accuracy' => 85,
        'gold' => 1000,
        'experience' => 0
    ];
    
    $mockCurrentLocation = (object) [
        'id' => 'road_a',
        'name' => 'プリマ街道',
        'type' => 'road'
    ];
    
    $mockNextLocation = (object) [
        'id' => 'town_b',
        'name' => 'セカンダ町',
        'type' => 'town'
    ];
    
    $mockMovementInfo = [
        'total_dice_count' => 2,
        'base_dice_count' => 1,
        'extra_dice' => 1,
        'dice_bonus' => 2,
        'movement_multiplier' => 1.0,
        'min_possible_movement' => 3,
        'max_possible_movement' => 14
    ];
    
    return view('game', [
        'gameState' => 'road',
        'player' => $mockPlayer,
        'character' => $mockPlayer,
        'currentLocation' => $mockCurrentLocation,
        'nextLocation' => $mockNextLocation,
        'movementInfo' => $mockMovementInfo
    ]);
})->name('layout.road.noright');

Route::get('/layout_fight_noright', function () {
    $mockCharacter = [
        'name' => 'テストプレイヤー',
        'level' => 1,
        'hp' => 85,
        'max_hp' => 100,
        'mp' => 15,
        'max_mp' => 20,
        'sp' => 25,
        'max_sp' => 30,
        'attack' => 10,
        'defense' => 8,
        'agility' => 12,
        'evasion' => 15,
        'magic_attack' => 8,
        'accuracy' => 85,
        'experience' => 0,
        'gold' => 1000
    ];
    
    $mockMonster = [
        'name' => 'スライム',
        'level' => 1,
        'hp' => 30,
        'max_hp' => 30,
        'attack' => 8,
        'defense' => 3,
        'agility' => 6,
        'evasion' => 10,
        'emoji' => '🟢'
    ];
    
    $mockBattle = [
        'turn' => 1,
        'phase' => 'player_turn',
        'log' => [
            '戦闘が開始されました！',
            'スライムが現れた！'
        ]
    ];
    
    return view('game', [
        'gameState' => 'battle',
        'player' => (object)$mockCharacter,
        'character' => $mockCharacter,
        'monster' => $mockMonster,
        'battle' => $mockBattle,
        'currentLocation' => null,
        'nextLocation' => null,
        'movementInfo' => []
    ]);
})->name('layout.battle.noright');

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
    Route::get('/api/location/facilities', [GameController::class, 'getLocationFacilities'])->name('api.location.facilities');
    
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
    
    // 町施設関連のルート
    Route::prefix('facilities')->group(function () {
        Route::get('/item', [ItemFacilityController::class, 'index'])->name('facilities.item.index');
        Route::post('/item/transaction', [ItemFacilityController::class, 'processTransaction'])->name('facilities.item.transaction');
        Route::get('/item/inventory', [ItemFacilityController::class, 'inventory'])->name('facilities.item.inventory');
        
        Route::get('/blacksmith', [BlacksmithFacilityController::class, 'index'])->name('facilities.blacksmith.index');
        Route::post('/blacksmith/transaction', [BlacksmithFacilityController::class, 'processTransaction'])->name('facilities.blacksmith.transaction');
        
        Route::get('/tavern', [TavernFacilityController::class, 'index'])->name('facilities.tavern.index');
        Route::post('/tavern/transaction', [TavernFacilityController::class, 'processTransaction'])->name('facilities.tavern.transaction');
        
        Route::get('/alchemy', [AlchemyFacilityController::class, 'index'])->name('facilities.alchemy.index');
        Route::post('/alchemy/perform', [AlchemyFacilityController::class, 'performAlchemy'])->name('facilities.alchemy.perform');
        Route::post('/alchemy/preview', [AlchemyFacilityController::class, 'previewAlchemy'])->name('facilities.alchemy.preview');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
