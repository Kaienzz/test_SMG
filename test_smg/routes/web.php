<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ItemShopController;
use App\Http\Controllers\BlacksmithController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/game', [GameController::class, 'index'])->name('game.index');
Route::post('/game/roll-dice', [GameController::class, 'rollDice'])->name('game.roll-dice');
Route::post('/game/move', [GameController::class, 'move'])->name('game.move');
Route::post('/game/move-to-next', [GameController::class, 'moveToNext'])->name('game.move-to-next');
Route::post('/game/reset', [GameController::class, 'reset'])->name('game.reset');

// Battle routes
Route::get('/battle', [BattleController::class, 'index'])->name('battle.index');
Route::post('/battle/start', [BattleController::class, 'startBattle'])->name('battle.start');
Route::post('/battle/attack', [BattleController::class, 'attack'])->name('battle.attack');
Route::post('/battle/defend', [BattleController::class, 'defend'])->name('battle.defend');
Route::post('/battle/escape', [BattleController::class, 'escape'])->name('battle.escape');
Route::post('/battle/skill', [BattleController::class, 'useSkill'])->name('battle.skill');
Route::post('/battle/end', [BattleController::class, 'endBattle'])->name('battle.end');

// キャラクター関連ルート
Route::get('/character', [CharacterController::class, 'index'])->name('character.index');
Route::get('/character/create', [CharacterController::class, 'create'])->name('character.create');
Route::post('/character', [CharacterController::class, 'store'])->name('character.store');
Route::get('/character/show', [CharacterController::class, 'show'])->name('character.show');
Route::post('/character/heal', [CharacterController::class, 'heal'])->name('character.heal');
Route::post('/character/restore-mp', [CharacterController::class, 'restoreMp'])->name('character.restore-mp');
Route::post('/character/gain-experience', [CharacterController::class, 'gainExperience'])->name('character.gain-experience');
Route::post('/character/take-damage', [CharacterController::class, 'takeDamage'])->name('character.take-damage');
Route::post('/character/reset', [CharacterController::class, 'reset'])->name('character.reset');

// インベントリー関連ルート
Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
Route::get('/inventory/show', [InventoryController::class, 'show'])->name('inventory.show');
Route::post('/inventory/add-item', [InventoryController::class, 'addItem'])->name('inventory.add-item');
Route::post('/inventory/remove-item', [InventoryController::class, 'removeItem'])->name('inventory.remove-item');
Route::post('/inventory/use-item', [InventoryController::class, 'useItem'])->name('inventory.use-item');
Route::post('/inventory/expand-slots', [InventoryController::class, 'expandSlots'])->name('inventory.expand-slots');
Route::get('/inventory/items-by-category', [InventoryController::class, 'getItemsByCategory'])->name('inventory.items-by-category');
Route::post('/inventory/add-sample-items', [InventoryController::class, 'addSampleItems'])->name('inventory.add-sample-items');
Route::post('/inventory/clear', [InventoryController::class, 'clearInventory'])->name('inventory.clear');
Route::get('/inventory/item-info', [InventoryController::class, 'getItemInfo'])->name('inventory.item-info');
Route::post('/inventory/move-item', [InventoryController::class, 'moveItem'])->name('inventory.move-item');

// 装備関連ルート
Route::get('/equipment', [EquipmentController::class, 'show'])->name('equipment.show');
Route::post('/equipment/equip', [EquipmentController::class, 'equip'])->name('equipment.equip');
Route::post('/equipment/unequip', [EquipmentController::class, 'unequip'])->name('equipment.unequip');
Route::get('/equipment/available-items', [EquipmentController::class, 'getAvailableItems'])->name('equipment.available-items');
Route::post('/equipment/add-sample', [EquipmentController::class, 'addSampleEquipment'])->name('equipment.add-sample');

// スキル関連ルート
Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
Route::post('/skills/use', [SkillController::class, 'useSkill'])->name('skills.use');
Route::post('/skills/add-sample', [SkillController::class, 'addSampleSkill'])->name('skills.add-sample');
Route::get('/skills/active-effects', [SkillController::class, 'getActiveEffects'])->name('skills.active-effects');
Route::post('/skills/decrease-durations', [SkillController::class, 'decreaseEffectDurations'])->name('skills.decrease-durations');

// ショップ関連ルート（抽象化後）
Route::get('/shops/item', [ItemShopController::class, 'index'])->name('shops.item.index');
Route::get('/shops/item/inventory', [ItemShopController::class, 'inventory'])->name('shops.item.inventory');
Route::post('/shops/item/transaction', [ItemShopController::class, 'processTransaction'])->name('shops.item.transaction');

Route::get('/shops/blacksmith', [BlacksmithController::class, 'index'])->name('shops.blacksmith.index');
Route::post('/shops/blacksmith/transaction', [BlacksmithController::class, 'processTransaction'])->name('shops.blacksmith.transaction');

// 旧ショップルート（互換性のため残す）
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::post('/shop/purchase', [ShopController::class, 'purchase'])->name('shop.purchase');
