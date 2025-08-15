<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminItemController;
use App\Http\Controllers\Admin\AdminMonsterController;

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
    
    // ダッシュボード
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
    // リアルタイム統計API
    Route::get('/api/stats/realtime', [DashboardController::class, 'realTimeStats'])->name('api.stats.realtime');
    Route::get('/api/analytics/detailed', [DashboardController::class, 'detailedAnalytics'])->name('api.analytics.detailed');
    
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
    
    // ゲームデータ管理（権限チェック付き）
    Route::middleware(['admin.permission:items.view'])->group(function () {
        // カスタムアイテム管理
        Route::get('/items', [AdminItemController::class, 'index'])->name('items.index');
        Route::get('/items/{item}', [AdminItemController::class, 'show'])->name('items.show');
        Route::get('/items/create', [AdminItemController::class, 'create'])->name('items.create');
        Route::get('/items/{item}/edit', [AdminItemController::class, 'edit'])->name('items.edit');
        
        // 標準アイテム管理
        Route::get('/items/standard', [AdminItemController::class, 'standardItems'])->name('items.standard');
        Route::get('/items/standard/create', [AdminItemController::class, 'createStandardItem'])->name('items.standard.create');
        Route::get('/items/standard/{itemId}', [AdminItemController::class, 'showStandardItem'])->name('items.standard.show');
        Route::get('/items/standard/{itemId}/edit', [AdminItemController::class, 'editStandardItem'])->name('items.standard.edit');
        
        // カスタムアイテム作成・編集（追加権限チェック）
        Route::middleware(['admin.permission:items.create'])->group(function () {
            Route::post('/items', [AdminItemController::class, 'store'])->name('items.store');
            Route::post('/items/standard', [AdminItemController::class, 'storeStandardItem'])->name('items.standard.store');
        });
        
        Route::middleware(['admin.permission:items.edit'])->group(function () {
            Route::put('/items/{item}', [AdminItemController::class, 'update'])->name('items.update');
            Route::put('/items/standard/{itemId}', [AdminItemController::class, 'updateStandardItem'])->name('items.standard.update');
            Route::post('/items/bulk-action', [AdminItemController::class, 'bulkAction'])->name('items.bulk_action');
            Route::post('/items/standard/backup', [AdminItemController::class, 'backupStandardItems'])->name('items.standard.backup');
        });
        
        // アイテム削除（特別権限）
        Route::middleware(['admin.permission:items.delete'])->group(function () {
            Route::delete('/items/{item}', [AdminItemController::class, 'destroy'])->name('items.destroy');
            Route::delete('/items/standard/{itemId}', [AdminItemController::class, 'deleteStandardItem'])->name('items.standard.delete');
        });
        
        // モンスター管理
        Route::get('/monsters', [AdminMonsterController::class, 'index'])->name('monsters.index');
        Route::get('/monsters/{monster}', [AdminMonsterController::class, 'show'])->name('monsters.show');
        Route::get('/monsters/{monster}/edit', [AdminMonsterController::class, 'edit'])->name('monsters.edit');
        
        // モンスター編集（追加権限チェック）
        Route::middleware(['admin.permission:monsters.edit'])->group(function () {
            Route::put('/monsters/{monster}', [AdminMonsterController::class, 'update'])->name('monsters.update');
            Route::post('/monsters/spawn-rates', [AdminMonsterController::class, 'updateSpawnRates'])->name('monsters.spawn_rates');
            Route::post('/monsters/balance-adjustment', [AdminMonsterController::class, 'balanceAdjustment'])->name('monsters.balance_adjustment');
        });
        
        Route::get('/shops', function () {
            return view('admin.shops.index', [
                'breadcrumb' => [
                    ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false],
                    ['title' => 'ショップ管理', 'active' => true]
                ]
            ]);
        })->name('shops.index');
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