<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminItemController;
use App\Http\Controllers\Admin\AdminMonsterController;
use App\Http\Controllers\Admin\AdminMonsterSpawnController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminRoadController;
use App\Http\Controllers\Admin\AdminDungeonController;
use App\Http\Controllers\Admin\AdminTownController;
use App\Http\Controllers\Admin\AdminTownFacilityController;
use App\Http\Controllers\Admin\AdminRouteConnectionController;
use App\Http\Controllers\Admin\AdminGatheringController;

/*
|--------------------------------------------------------------------------
| 管理者ルート
|--------------------------------------------------------------------------
|
| ここでは管理者専用のルートを定義します。
| 全てのルートは 'admin' および 'auth' ミドルウェアで保護されています。
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // ダッシュボード（権限チェック付き）
    Route::middleware(['admin.permission:dashboard.view'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    });
    
    // リアルタイム統計API（権限チェック付き）
    Route::middleware(['admin.permission:analytics.view'])->group(function () {
        Route::get('/api/stats/realtime', [DashboardController::class, 'realTimeStats'])->name('api.stats.realtime');
    });
    
    Route::middleware(['admin.permission:analytics.advanced'])->group(function () {
        Route::get('/api/analytics/detailed', [DashboardController::class, 'detailedAnalytics'])->name('api.analytics.detailed');
    });
    
    // ユーザー管理（権限チェック付き）
    Route::middleware(['admin.permission:users.view'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::get('/users/online', [AdminUserController::class, 'online'])->name('users.online');
        
        // ユーザー編集・操作（追加権限チェック）
        Route::middleware(['admin.permission:users.edit'])->group(function () {
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::post('/users/{user}/force-logout', [AdminUserController::class, 'forceLogout'])->name('users.force_logout');
            Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->name('users.bulk_action');
        });
        
        // ユーザー停止・復活（特別権限）
        Route::middleware(['admin.permission:users.suspend'])->group(function () {
            Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
            Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        });
        
        Route::get('/players', function () {
            return view('admin.players.index', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => 'プレイヤー管理', 'active' => true]
                ]
            ]);
        })->name('players.index');
    });
    
    // アイテム管理（権限チェック付き）
    Route::middleware(['admin.permission:items.view'])->group(function () {
        // カスタムアイテム管理
        Route::get('/items', [AdminItemController::class, 'index'])->name('items.index');
        Route::get('/items/{itemId}', [AdminItemController::class, 'show'])->name('items.show');
        Route::get('/items/create', [AdminItemController::class, 'create'])->name('items.create');
        
        
        // カスタムアイテム作成・編集（追加権限チェック）
        Route::middleware(['admin.permission:items.create'])->group(function () {
            Route::post('/items', [AdminItemController::class, 'store'])->name('items.store');
        });
        
        Route::middleware(['admin.permission:items.edit'])->group(function () {
            Route::get('/items/{itemId}/edit', [AdminItemController::class, 'edit'])->name('items.edit')
                 ->where('itemId', '[a-zA-Z][a-zA-Z0-9_-]*');
            Route::put('/items/{itemId}', [AdminItemController::class, 'update'])->name('items.update')
                 ->where('itemId', '[a-zA-Z][a-zA-Z0-9_-]*');
            Route::post('/items/bulk-action', [AdminItemController::class, 'bulkAction'])->name('items.bulk_action');
        });
        
        // アイテム削除（特別権限）
        Route::middleware(['admin.permission:items.delete'])->group(function () {
            Route::delete('/items/{itemId}', [AdminItemController::class, 'destroy'])->name('items.destroy')
                 ->where('itemId', '[a-zA-Z][a-zA-Z0-9_-]*');
        });
    });
    
    // モンスター管理（権限チェック付き）
    Route::middleware(['admin.permission:monsters.view'])->group(function () {
        // モンスター基本管理
        Route::get('/monsters', [AdminMonsterController::class, 'index'])->name('monsters.index');
        Route::get('/monsters/{monster}', [AdminMonsterController::class, 'show'])->name('monsters.show');
        Route::get('/monsters/{monster}/edit', [AdminMonsterController::class, 'edit'])->name('monsters.edit');
        
        // モンスタースポーン管理（統合版）
        Route::get('/monster-spawns', [AdminMonsterSpawnController::class, 'index'])->name('monster-spawns.index');
        Route::get('/monster-spawns/location/{locationId}', [AdminMonsterSpawnController::class, 'show'])->name('monster-spawns.show');
        Route::get('/monster-spawns/location/{locationId}/create', [AdminMonsterSpawnController::class, 'create'])->name('monster-spawns.create');
        Route::get('/monster-spawns/{spawnId}/edit', [AdminMonsterSpawnController::class, 'edit'])->name('monster-spawns.edit');
        
        // モンスター編集（追加権限チェック）
        Route::middleware(['admin.permission:monsters.edit'])->group(function () {
            Route::put('/monsters/{monster}', [AdminMonsterController::class, 'update'])->name('monsters.update');
            Route::post('/monsters/spawn-rates', [AdminMonsterController::class, 'updateSpawnRates'])->name('monsters.spawn_rates');
            Route::post('/monsters/balance-adjustment', [AdminMonsterController::class, 'balanceAdjustment'])->name('monsters.balance_adjustment');
            
            // モンスタースポーン編集（統合版）
            Route::post('/monster-spawns', [AdminMonsterSpawnController::class, 'store'])->name('monster-spawns.store');
            Route::put('/monster-spawns/{spawnId}', [AdminMonsterSpawnController::class, 'update'])->name('monster-spawns.update');
            Route::post('/monster-spawns/bulk-action', [AdminMonsterSpawnController::class, 'bulkAction'])->name('monster-spawns.bulk-action');
        });
        
        // モンスタースポーン削除（特別権限）
        Route::middleware(['admin.permission:monsters.delete'])->group(function () {
            Route::delete('/monster-spawns/{spawnId}', [AdminMonsterSpawnController::class, 'destroy'])->name('monster-spawns.destroy');
        });
    });
        
    // 町施設管理（権限チェック付き）
    // ⚠️ 重要：固定パス（create）を動的パス（{facility}）より先に登録
    
    // Create権限（最優先）
    Route::middleware(['admin.permission:town_facilities.create'])->group(function () {
        Route::get('/town-facilities/create', [AdminTownFacilityController::class, 'create'])->name('town-facilities.create');
        Route::post('/town-facilities', [AdminTownFacilityController::class, 'store'])->name('town-facilities.store');
        Route::post('/town-facilities/check-duplicate', [AdminTownFacilityController::class, 'checkDuplicate'])->name('town-facilities.check-duplicate');
    });
    
    // View権限
    Route::middleware(['admin.permission:town_facilities.view'])->group(function () {
        Route::get('/town-facilities', [AdminTownFacilityController::class, 'index'])->name('town-facilities.index');
        Route::get('/town-facilities/{facility}', [AdminTownFacilityController::class, 'show'])->name('town-facilities.show');
    });
    
    // Edit権限
    Route::middleware(['admin.permission:town_facilities.edit'])->group(function () {
        Route::get('/town-facilities/{facility}/edit', [AdminTownFacilityController::class, 'edit'])->name('town-facilities.edit');
        Route::put('/town-facilities/{facility}', [AdminTownFacilityController::class, 'update'])->name('town-facilities.update');
        Route::patch('/town-facilities/{facility}/config', [AdminTownFacilityController::class, 'updateConfig'])->name('town-facilities.update-config');
        Route::post('/town-facilities/{facility}/items', [AdminTownFacilityController::class, 'addItem'])->name('town-facilities.add-item');
        Route::put('/town-facilities/{facility}/items/{item}', [AdminTownFacilityController::class, 'updateItem'])->name('town-facilities.update-item');
        Route::delete('/town-facilities/{facility}/items/{item}', [AdminTownFacilityController::class, 'deleteItem'])->name('town-facilities.delete-item');
    });
    
    // Delete権限
    Route::middleware(['admin.permission:town_facilities.delete'])->group(function () {
        Route::delete('/town-facilities/{facility}', [AdminTownFacilityController::class, 'destroy'])->name('town-facilities.destroy');
    });
    
    // ロケーション管理（統計・ダッシュボード）
    Route::middleware(['admin.permission:locations.view'])->group(function () {
        // ロケーション管理トップページ（統計ダッシュボード）
        Route::get('/locations', [AdminLocationController::class, 'index'])->name('locations.index');
        
        // ロケーション詳細表示（汎用）
        Route::get('/locations/{locationId}', [AdminLocationController::class, 'show'])
             ->name('locations.show')
             ->where('locationId', '[a-zA-Z][a-zA-Z0-9_-]*');
             
        // ロケーションデータのエクスポート
        Route::get('/locations/export', [AdminLocationController::class, 'export'])->name('locations.export');
        
        // ロケーションデータのインポート・復元
        Route::post('/locations/import', [AdminLocationController::class, 'import'])->name('locations.import');
        Route::post('/locations/restore', [AdminLocationController::class, 'restore'])->name('locations.restore');
             
        // スポーンリスト管理（モンスター管理と統合予定）
        // 削除: 重複ルート - /monster-spawns で代替
        // Route::get('/locations/spawn-lists', [AdminLocationController::class, 'spawnLists'])->name('locations.spawn-lists');
    });
    
    // Road管理（新分離型システム）
    Route::middleware(['admin.permission:locations.view'])->group(function () {
        Route::resource('roads', AdminRoadController::class);
    });
    
    // Town管理（新分離型システム）
    Route::middleware(['admin.permission:locations.view'])->group(function () {
        Route::resource('towns', AdminTownController::class);
    });
    
    // Route Connection管理（新分離型システム）
    Route::middleware(['admin.permission:locations.view'])->group(function () {
        Route::resource('route-connections', AdminRouteConnectionController::class);
        Route::get('route-connections-validate', [AdminRouteConnectionController::class, 'validate'])->name('route-connections.validate');
        Route::get('route-connections-graph-data', [AdminRouteConnectionController::class, 'graphData'])->name('route-connections.graph-data');
        Route::get('route-connections-test-graph', function () {
            return view('admin.route-connections.test-graph');
        })->name('route-connections.test-graph');
        Route::get('route-connections-debug', function () {
            return view('admin.route-connections.debug');
        })->name('route-connections.debug');
    });
    
    // 採集管理（新統合システム）
    Route::middleware(['admin.permission:gathering.view'])->group(function () {
        Route::get('/gathering', [AdminGatheringController::class, 'index'])->name('gathering.index');
        Route::get('/gathering/stats', [AdminGatheringController::class, 'stats'])->name('gathering.stats');
        Route::get('/gathering/routes/{routeId}', [AdminGatheringController::class, 'getRouteGatheringData'])->name('gathering.routes.data');
         
        Route::middleware(['admin.permission:gathering.create'])->group(function () {
            Route::get('/gathering/create', [AdminGatheringController::class, 'create'])->name('gathering.create');
            Route::post('/gathering', [AdminGatheringController::class, 'store'])->name('gathering.store');
            Route::post('/gathering/migrate-from-legacy', [AdminGatheringController::class, 'migrateFromLegacy'])->name('gathering.migrate-from-legacy');
        });
        
        Route::middleware(['admin.permission:gathering.edit'])->group(function () {
            Route::get('/gathering/{mapping}/edit', [AdminGatheringController::class, 'edit'])->name('gathering.edit');
            Route::put('/gathering/{mapping}', [AdminGatheringController::class, 'update'])->name('gathering.update');
            Route::patch('/gathering/{mapping}/toggle', [AdminGatheringController::class, 'toggle'])->name('gathering.toggle');
        });
        
        Route::middleware(['admin.permission:gathering.delete'])->group(function () {
            Route::delete('/gathering/{mapping}', [AdminGatheringController::class, 'destroy'])->name('gathering.destroy');
        });
    });
    
    // Dungeon管理（新分離型システム - DungeonDescベース）
    Route::middleware(['admin.permission:locations.view'])->group(function () {
        Route::resource('dungeons', AdminDungeonController::class);
        
        // ダンジョンフロア管理（追加ルート）
        Route::get('dungeons/{dungeon}/floors', [AdminDungeonController::class, 'floors'])->name('dungeons.floors');
        Route::get('dungeons/{dungeon}/create-floor', [AdminDungeonController::class, 'createFloor'])->name('dungeons.create-floor');
        Route::post('dungeons/{dungeon}/floors', [AdminDungeonController::class, 'storeFloor'])->name('dungeons.floors.store');
    });
    
    // 分析・監視（権限チェック付き）
    Route::middleware(['admin.permission:analytics.view'])->group(function () {
        Route::get('/analytics', function () {
            return view('admin.analytics.index', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => '分析ダッシュボード', 'active' => true]
                ]
            ]);
        })->name('analytics.index');
        
        Route::get('/audit', function () {
            return view('admin.audit.index', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => '監査ログ', 'active' => true]
                ]
            ]);
        })->name('audit.index');
    });
    
    // システム管理（権限チェック付き）
    Route::middleware(['admin.permission:system.config'])->group(function () {
        Route::get('/system/config', function () {
            return view('admin.system.config', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => 'システム設定', 'active' => true]
                ]
            ]);
        })->name('system.config');
        
        Route::get('/roles', function () {
            return view('admin.roles.index', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => 'ロール・権限管理', 'active' => true]
                ]
            ]);
        })->name('roles.index');
    });
    
});