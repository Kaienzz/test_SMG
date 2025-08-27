# Tasks_25AUG2025#Saisyuu1.md - 採集機能構築分析レポート

**作成日**: 2025年8月25日  
**分析者**: GitHub Copilot  
**対象**: 採集関連機能の再構築計画  

---

## 🔍 現状分析結果

### 📊 現在の採集システム実装状況

#### 1. **データ管理**: ハードコード方式
- **ファイル**: `app/Models/GatheringTable.php`
- **方式**: 配列ベースの静的データ管理
- **問題点**: 
  - 管理画面からの編集不可
  - スケーラビリティの不足
  - バリエーション追加の困難

#### 2. **採集ロジック**: 実装済み
- **コントローラー**: `app/Http/Controllers/GatheringController.php`
- **API実装**: ✅ 完了
  - `POST /gathering/gather` - 採集実行
  - `GET /gathering/info` - 採集情報取得
- **ゲームロジック**: ✅ 動作中
  - SPコスト計算
  - スキルレベル制限
  - 成功率・数量計算

#### 3. **UI実装**: マルチレイアウト対応
- **実装ファイル**:
  - `resources/views/game/partials/location_info.blade.php`
  - `resources/views/game-states/road-*.blade.php`
- **機能**: 
  - 採集ボタン・情報ボタン表示
  - スキルレベル表示
  - 権限ベース表示制御

#### 4. **データベース構造**: Routes中心設計
- **メインテーブル**: `routes` (旧game_locations)
- **カテゴリ**: `road`, `town`, `dungeon`
- **採集対象**: `road`カテゴリのみ実装（**拡張要求**: `dungeon`カテゴリも対応）

---

## 🎯 要件分析

### ✅ 満たすべき要件
1. **管理のしやすい採集管理機能をAdmin管理画面に実装**
2. **各マップ(Routes)との1対1接続**
3. **現在のゲームロジック維持**
4. **レファレンスドキュメント準拠の実装**
5. **🆕 Road・Dungeon両方での採集対応** (新要件)

### 🔧 技術要件
- **Laravel 11** MVC アーキテクチャ
- **AdminController基底クラス** 継承
- **権限ベースアクセス制御** (RBAC)
- **監査ログシステム** 統合
- **統一デザインシステム** 適用
- **🆕 Road・Dungeon両対応採集システム** (拡張要件)

---

## � Road・Dungeon両対応採集システム拡張分析

### 📊 現在のダンジョン実装状況

#### 1. **ダンジョンデータ構造**: 階層化設計
- **ダンジョン定義**: `dungeons_desc` テーブル
- **フロア管理**: `routes` テーブル（`category='dungeon'`, `dungeon_id`で紐付け）
- **既存ダンジョン**:
  - `dungeon_1`: 古の洞窟（1F, Lv3-10）
  - `dungeon_2`: 忘れられた遺跡（1F, Lv8-20）
  - `dungeon_secret_room`: 隠し部屋（1F, Lv1-5）
  - `test_pyramid_1f`: テストピラミッド1階

#### 2. **ダンジョン特性と採集の関連性**
- **環境差異**: 洞窟・遺跡・地下空間は道路と異なる採集環境
- **レアリティ**: ダンジョンは一般的により希少なアイテムが採集可能
- **危険度**: `min_level`/`max_level`に基づく採集難易度
- **フロア別差異**: 深いフロアほど高品質なアイテム

#### 3. **UI実装現状**: 道路専用
- **制限**: 現在の採集ボタンは「道での行動」セクションにのみ表示
- **表示条件**: `$currentLocation['category'] === 'road'` でのみ採集UI表示
- **ラベリング**: "道での行動" ハードコード

#### 4. **ゲームロジック制限**: Road Only
- **採集実行**: `$player->location_type !== 'road'` でエラー
- **採集情報**: 同様にroad限定チェック

### 🎯 Road・Dungeon両対応設計

#### A. **採集環境分類システム**
```php
// 採集環境タイプ定義（シンプル版）
enum GatheringEnvironment: string 
{
    case ROAD = 'road';           // 道路環境
    case DUNGEON = 'dungeon';     // ダンジョン環境
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::ROAD => '道路',
            self::DUNGEON => 'ダンジョン',
        };
    }
}
```

#### B. **拡張GatheringMappingテーブル設計**
```sql
CREATE TABLE gathering_mappings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    route_id VARCHAR(255) NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    
    -- 基本採集設定
    required_skill_level INT NOT NULL DEFAULT 1,
    success_rate INT NOT NULL,
    quantity_min INT NOT NULL DEFAULT 1,
    quantity_max INT NOT NULL DEFAULT 1,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_route_id (route_id),
    INDEX idx_item_id (item_id),
    INDEX idx_skill_level (required_skill_level),
    INDEX idx_active (is_active),
    
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_route_item (route_id, item_id)
);
```

#### C. **Route モデル拡張**
```php
// app/Models/Route.php に追加

/**
 * 採集可能判定（Road・Dungeon対応）
 */
public function hasGatheringItems(): bool
{
    return in_array($this->category, ['road', 'dungeon']) 
           && $this->gatheringMappings()->exists();
}
```

---

## �🏗️ 推奨アーキテクチャ設計

### 1. **データベース設計**: 拡張性重視

#### A. **GatheringMapping テーブル** (新規作成)
```sql
CREATE TABLE gathering_mappings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    route_id VARCHAR(255) NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    required_skill_level INT NOT NULL DEFAULT 1,
    success_rate INT NOT NULL,        -- 1-100
    quantity_min INT NOT NULL DEFAULT 1,
    quantity_max INT NOT NULL DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_route_id (route_id),
    INDEX idx_item_id (item_id),
    INDEX idx_skill_level (required_skill_level),
    INDEX idx_active (is_active),
    
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_route_item (route_id, item_id)
);
```

#### B. **GatheringCategory テーブル** (将来拡張用)
```sql
CREATE TABLE gathering_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_class VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### 2. **モデル・リレーション設計**

#### A. **GatheringMapping モデル**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatheringMapping extends Model
{
    protected $fillable = [
        'route_id',
        'item_id', 
        'required_skill_level',
        'success_rate',
        'quantity_min',
        'quantity_max',
        'is_active',
    ];

    protected $casts = [
        'required_skill_level' => 'integer',
        'success_rate' => 'integer',
        'quantity_min' => 'integer',
        'quantity_max' => 'integer',
        'is_active' => 'boolean',
    ];

    // リレーション
    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // スコープ
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSkillLevel($query, int $skillLevel)
    {
        return $query->where('required_skill_level', '<=', $skillLevel);
    }

    // 業務ロジック
    public function calculateSuccessRate(int $playerSkillLevel): int
    {
        $baseRate = $this->success_rate;
        $skillBonus = max(0, ($playerSkillLevel - $this->required_skill_level) * 5);
        $finalRate = $baseRate + $skillBonus;
        
        return min(100, (int)$finalRate);
    }

    /**
     * 🆕 ダンジョン採集可能判定（シンプル版）
     */
    public function canGatherInDungeon(int $playerLevel): bool
    {
        // プレイヤーレベル要件（ダンジョンの推奨レベルとマッチング）
        $route = $this->route;
        if ($route && $route->min_level && $playerLevel < $route->min_level) {
            return false;
        }
        
        return true;
    }
}
```

#### B. **Route モデル拡張**
```php
// app/Models/Route.php に追加

/**
 * 採集マッピング
 */
public function gatheringMappings()
{
    return $this->hasMany(GatheringMapping::class, 'route_id', 'id')
                ->where('is_active', true)
                ->orderBy('required_skill_level')
                ->orderBy('success_rate', 'desc');
}

/**
 * アクティブな採集アイテム
 */
public function gatheringItems()
{
    return $this->belongsToMany(Item::class, 'gathering_mappings', 'route_id', 'item_id')
                ->withPivot([
                    'required_skill_level',
                    'success_rate', 
                    'quantity_min',
                    'quantity_max'
                ])
                ->wherePivot('is_active', true);
}

/**
 * 採集可能判定（Road・Dungeon対応）
 */
public function hasGatheringItems(): bool
{
    return in_array($this->category, ['road', 'dungeon']) 
           && $this->gatheringMappings()->exists();
}
```

### 3. **サービスクラス設計**

#### A. **AdminGatheringService**
```php
<?php

namespace App\Services\Admin;

use App\Models\GatheringMapping;
use App\Models\Route;
use App\Models\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminGatheringService
{
    /**
     * 🆕 環境別採集マッピング一覧取得
     */
    public function getGatheringMappingsByEnvironment(array $filters = []): Collection
    {
        $query = GatheringMapping::with(['route', 'item']);
        
        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }
        
        if (!empty($filters['gathering_environment'])) {
            $query->where('gathering_environment', $filters['gathering_environment']);
        }
        
        if (!empty($filters['item_category'])) {
            $query->whereHas('item', function($q) use ($filters) {
                $q->where('category', $filters['item_category']);
            });
        }
        
        if (isset($filters['skill_level'])) {
            $query->where('required_skill_level', '<=', $filters['skill_level']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->orderBy('gathering_environment')
                    ->orderBy('required_skill_level')
                    ->get();
    }
    public function getGatheringMappings(array $filters = []): Collection
    {
        // 🆕 環境別フィルタリング対応版を使用
        return $this->getGatheringMappingsByEnvironment($filters);
    }

    /**
     * 🆕 環境別採集統計
     */
    public function getGatheringStatsByEnvironment(): array
    {
        return Route::whereIn('category', ['road', 'dungeon'])
            ->withCount(['gatheringMappings as total_items'])
            ->withCount(['gatheringMappings as active_items' => function($q) {
                $q->where('is_active', true);
            }])
            ->get()
            ->groupBy('category')
            ->map(function($routes, $category) {
                return [
                    'category' => $category,
                    'category_name' => $category === 'road' ? '道路' : 'ダンジョン',
                    'total_routes' => $routes->count(),
                    'routes_with_gathering' => $routes->where('total_items', '>', 0)->count(),
                    'total_gathering_items' => $routes->sum('total_items'),
                    'active_gathering_items' => $routes->sum('active_items'),
                    'routes' => $routes->map(function($route) {
                        return [
                            'route_id' => $route->id,
                            'route_name' => $route->name,
                            'environment' => $route->gathering_environment ?? 'road',
                            'total_items' => $route->total_items,
                            'active_items' => $route->active_items,
                            'completion_rate' => $route->total_items > 0 
                                ? round(($route->active_items / $route->total_items) * 100, 1)
                                : 0,
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * ルート別採集統計
     */
    public function getGatheringStatsByRoute(): array
    {
        // 🆕 環境別統計を展開してルート別に変換
        $environmentStats = $this->getGatheringStatsByEnvironment();
        
        $routes = [];
        foreach ($environmentStats as $envStat) {
            $routes = array_merge($routes, $envStat['routes']);
        }
        
        return $routes;
    }

    /**
     * バルクインポート（既存データ移行用）
     */
    public function bulkImportFromGatheringTable(): array
    {
        $importedCount = 0;
        $errors = [];
        
        try {
            $gatheringData = \App\Models\GatheringTable::getGatheringTableByRoad('road_1');
            // 実装: 既存データの一括変換処理
            
        } catch (\Exception $e) {
            Log::error('Gathering bulk import failed', ['error' => $e->getMessage()]);
            $errors[] = $e->getMessage();
        }
        
        return [
            'imported_count' => $importedCount,
            'errors' => $errors,
        ];
    }

    /**
     * 採集データの整合性チェック（Road/Dungeon対応）
     */
    public function validateGatheringData(array $data): array
    {
        $errors = [];
        
        // ルート存在チェック
        $route = Route::find($data['route_id']);
        if (!$route) {
            $errors[] = 'ルートが存在しません';
            return $errors;
        }
        
        // RoadかDungeonのみ採集可能
        if (!in_array($route->category, ['road', 'dungeon'])) {
            $errors[] = '採集はRoadまたはDungeonでのみ可能です';
            return $errors;
        }
        
        // 環境とルートカテゴリの整合性チェック（シンプル版）
        if (!$this->isValidEnvironmentForRoute($data['gathering_environment'] ?? 'road', $route->category)) {
            $errors[] = '選択された環境はこのルートタイプに対応していません';
        }
        
        // 重複チェック
        if ($this->isDuplicateGathering($data['route_id'], $data['item_id'], $data['gathering_environment'] ?? 'road')) {
            $errors[] = 'このルート・アイテム・環境の組み合わせは既に存在します';
        }
        
        return $errors;
    }

    /**
     * 環境とルートカテゴリの整合性チェック（シンプル版）
     */
    private function isValidEnvironmentForRoute(string $environment, string $routeCategory): bool
    {
        // Road: road環境のみ
        if ($routeCategory === 'road') {
            return $environment === 'road';
        }
        
        // Dungeon: dungeon環境のみ
        if ($routeCategory === 'dungeon') {
            return $environment === 'dungeon';
        }
        
        // その他（town等）は採集不可
        return false;
    }

    /**
     * 重複採集設定チェック
     */
    private function isDuplicateGathering(string $routeId, int $itemId, string $environment): bool
    {
        return GatheringMapping::where('route_id', $routeId)
            ->where('item_id', $itemId)
            ->where('gathering_environment', $environment)
            ->exists();
    }
}
```

### 4. **管理画面コントローラー設計**

#### A. **AdminGatheringController** (新規作成)
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\GatheringMapping;
use App\Models\Route;
use App\Models\Item;
use App\Services\Admin\AdminGatheringService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminGatheringController extends AdminController
{
    private AdminGatheringService $gatheringService;

    public function __construct(
        AdminAuditService $auditService,
        AdminGatheringService $gatheringService
    ) {
        parent::__construct($auditService);
        $this->gatheringService = $gatheringService;
    }

    /**
     * 採集管理トップページ
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.view');
        $this->trackPageAccess('gathering.index');

        try {
            // フィルタ処理
            $filters = $request->only(['route_id', 'item_category', 'skill_level', 'is_active']);
            
            // データ取得
            $gatheringMappings = $this->gatheringService->getGatheringMappings($filters);
            $routeStats = $this->gatheringService->getGatheringStatsByRoute();
            $routes = Route::whereIn('category', ['road', 'dungeon'])->orderBy('name')->get();
            $itemCategories = Item::distinct('category')->pluck('category');

            $this->auditLog('gathering.index.viewed', [
                'total_mappings' => $gatheringMappings->count(),
                'filters' => $filters,
            ], 'low');

            return view('admin.gathering.index', compact(
                'gatheringMappings',
                'routeStats', 
                'routes',
                'itemCategories',
                'filters'
            ));

        } catch (\Exception $e) {
            $this->auditLog('gathering.index.failed', [
                'error' => $e->getMessage()
            ], 'high');
            
            return view('admin.gathering.index', [
                'error' => '採集データの読み込みに失敗しました: ' . $e->getMessage(),
                'gatheringMappings' => collect(),
                'routeStats' => [],
                'routes' => collect(),
                'itemCategories' => collect(),
                'filters' => [],
            ]);
        }
    }

    /**
     * 採集マッピング作成
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.create');

        $validated = $request->validate([
            'route_id' => ['required', 'string', Rule::exists('routes', 'id')],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'required_skill_level' => ['required', 'integer', 'min:1', 'max:100'],
            'success_rate' => ['required', 'integer', 'min:1', 'max:100'],
            'quantity_min' => ['required', 'integer', 'min:1'],
            'quantity_max' => ['required', 'integer', 'min:1', 'gte:quantity_min'],
            'is_active' => ['boolean'],
        ]);

        try {
            // 整合性チェック実行
            $validationErrors = $this->gatheringService->validateGatheringData($validated);
            if (!empty($validationErrors)) {
                return back()->withInput()
                            ->withErrors(['validation' => $validationErrors])
                            ->with('error', '入力データに問題があります: ' . implode(', ', $validationErrors));
            }

            DB::beginTransaction();

            $mapping = GatheringMapping::create($validated);

            $this->auditLog('gathering.mapping.created', [
                'mapping_id' => $mapping->id,
                'route_id' => $mapping->route_id,
                'item_id' => $mapping->item_id,
                'data' => $validated,
            ], 'medium');

            DB::commit();

            return redirect()->route('admin.gathering.index')
                           ->with('success', '採集マッピングを作成しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->auditLog('gathering.mapping.create_failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ], 'high');

            return back()->withInput()
                        ->with('error', '採集マッピングの作成に失敗しました: ' . $e->getMessage());
        }
    }

    // update, destroy等の実装...
}
```

#### B. **AdminRoadController 拡張**
```php
// 既存のAdminRoadController に採集機能追加

/**
 * Road詳細表示（採集情報付き）
 */
public function show(Request $request, string $id)
{
    $this->initializeForRequest();
    $this->checkPermission('locations.view');

    try {
        $road = Route::whereIn('category', ['road', 'dungeon'])
                    ->with(['gatheringMappings.item'])
                    ->where('id', $id)
                    ->first();

        if (!$road) {
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Route が見つかりませんでした。');
        }

        // 採集統計計算
        $gatheringStats = [
            'total_items' => $road->gatheringMappings->count(),
            'active_items' => $road->gatheringMappings->where('is_active', true)->count(),
            'route_type' => $road->route_type,
            'skill_level_range' => [
                'min' => $road->gatheringMappings->min('required_skill_level') ?? 0,
                'max' => $road->gatheringMappings->max('required_skill_level') ?? 0,
            ],
            'success_rate_avg' => round($road->gatheringMappings->avg('success_rate') ?? 0, 1),
        ];

        $this->auditLog('roads.show.viewed', [
            'road_id' => $id,
            'road_name' => $road->name,
            'route_type' => $road->route_type,
            'gathering_items_count' => $gatheringStats['total_items'],
        ]);

        return view('admin.roads.show', compact('road', 'gatheringStats'));

    } catch (\Exception $e) {
        // エラーハンドリング
    }
}
```

### 5. **ルーティング設計**

#### routes/admin.php 拡張
```php
// 採集管理（新規追加）
Route::middleware(['admin.permission:gathering.view'])->group(function () {
    Route::get('/gathering', [AdminGatheringController::class, 'index'])
         ->name('gathering.index');
    Route::get('/gathering/stats', [AdminGatheringController::class, 'stats'])
         ->name('gathering.stats');
    Route::get('/gathering/export', [AdminGatheringController::class, 'export'])
         ->name('gathering.export');
         
    Route::middleware(['admin.permission:gathering.create'])->group(function () {
        Route::get('/gathering/create', [AdminGatheringController::class, 'create'])
             ->name('gathering.create');
        Route::post('/gathering', [AdminGatheringController::class, 'store'])
             ->name('gathering.store');
        Route::post('/gathering/bulk-import', [AdminGatheringController::class, 'bulkImport'])
             ->name('gathering.bulk-import');
    });
    
    Route::middleware(['admin.permission:gathering.edit'])->group(function () {
        Route::get('/gathering/{mapping}/edit', [AdminGatheringController::class, 'edit'])
             ->name('gathering.edit');
        Route::put('/gathering/{mapping}', [AdminGatheringController::class, 'update'])
             ->name('gathering.update');
        Route::patch('/gathering/{mapping}/toggle', [AdminGatheringController::class, 'toggle'])
             ->name('gathering.toggle');
    });
    
    Route::middleware(['admin.permission:gathering.delete'])->group(function () {
        Route::delete('/gathering/{mapping}', [AdminGatheringController::class, 'destroy'])
             ->name('gathering.destroy');
    });
});

// Road管理の採集サブ機能
Route::middleware(['admin.permission:locations.view'])->group(function () {
    Route::get('/roads/{road}/gathering', [AdminRoadController::class, 'gatheringSettings'])
         ->name('roads.gathering');
    Route::post('/roads/{road}/gathering/quick-add', [AdminRoadController::class, 'quickAddGathering'])
         ->name('roads.gathering.quick-add');
});
```

### 6. **ビューテンプレート設計**

#### A. **ナビゲーション統合**
```php
{{-- resources/views/admin/layouts/app.blade.php 更新 --}}

<!-- マップ管理セクション内に採集管理追加 -->
<div class="admin-nav-submenu">
    <a href="{{ route('admin.roads.index') }}" class="admin-nav-subitem {{ request()->routeIs('admin.roads*') ? 'active' : '' }}">
        <svg class="admin-nav-icon">...</svg>
        道路管理
    </a>
    {{-- 新規追加 --}}
    <a href="{{ route('admin.gathering.index') }}" class="admin-nav-subitem {{ request()->routeIs('admin.gathering*') ? 'active' : '' }}">
        <svg class="admin-nav-icon" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        採集管理
    </a>
    <a href="{{ route('admin.towns.index') }}" class="admin-nav-subitem {{ request()->routeIs('admin.towns*') ? 'active' : '' }}">
        <svg class="admin-nav-icon">...</svg>
        町管理
    </a>
    <!-- 既存項目... -->
</div>
```

#### B. **メイン管理画面テンプレート**
```php
{{-- resources/views/admin/gathering/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', '採集管理')
@section('subtitle', 'ゲーム内採集システムの統合管理')

@section('content')
<div class="admin-content-container">
    
    <!-- ページヘッダー -->
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">採集管理</h1>
            <p class="page-subtitle">各ルートでの採集可能アイテムを管理します</p>
        </div>
        <div class="page-header-actions">
            @if(auth()->user()->can('gathering.create'))
            <a href="{{ route('admin.gathering.create') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> 新しい採集設定
            </a>
            @endif
            @if(auth()->user()->can('gathering.view'))
            <a href="{{ route('admin.gathering.stats') }}" class="admin-btn admin-btn-info">
                <i class="fas fa-chart-bar"></i> 統計表示
            </a>
            @endif
        </div>
    </div>

    <!-- 統計サマリー -->
    <div class="admin-stats-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon bg-primary">
                <i class="fas fa-map"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">採集可能ルート</div>
                <div class="admin-stat-value">{{ count($routeStats) }}</div>
                <div class="admin-stat-subtitle">
                    道路: {{ collect($environmentStats)->where('category', 'road')->first()['total_routes'] ?? 0 }} / 
                    ダンジョン: {{ collect($environmentStats)->where('category', 'dungeon')->first()['total_routes'] ?? 0 }}
                </div>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="admin-stat-icon bg-success">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">総採集アイテム</div>
                <div class="admin-stat-value">{{ $gatheringMappings->count() }}</div>
                <div class="admin-stat-subtitle">
                    環境別設定: {{ $gatheringMappings->groupBy('gathering_environment')->count() }}種類
                </div>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="admin-stat-icon bg-info">
                <i class="fas fa-toggle-on"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">アクティブ設定</div>
                <div class="admin-stat-value">{{ $gatheringMappings->where('is_active', true)->count() }}</div>
                <div class="admin-stat-subtitle">
                    アクティブな採集設定数
                </div>
            </div>
        </div>
        
        <div class="admin-stat-card">
            <div class="admin-stat-icon bg-warning">
                <i class="fas fa-dungeon"></i>
            </div>
            <div class="admin-stat-content">
                <div class="admin-stat-label">ダンジョン採集</div>
                <div class="admin-stat-value">{{ $gatheringMappings->where('gathering_environment', 'dungeon')->count() }}</div>
                <div class="admin-stat-subtitle">
                    ダンジョン環境での採集設定
                </div>
            </div>
        </div>
    </div>

    <!-- フィルタリング -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>フィルタ・検索</h3>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.gathering.index') }}" class="admin-filter-form">
                <div class="admin-filter-row">
                    <div class="admin-form-group">
                        <label class="admin-form-label">ルート</label>
                        <select name="route_id" class="admin-form-input">
                            <option value="">全てのルート</option>
                            @foreach($routes as $route)
                            <option value="{{ $route->id }}" {{ request('route_id') === $route->id ? 'selected' : '' }}>
                                [{{ $route->category === 'road' ? '道路' : 'ダンジョン' }}] {{ $route->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">採集環境</label>
                        <select name="gathering_environment" class="admin-form-input">
                            <option value="">全ての環境</option>
                            @foreach($gatheringEnvironments as $env)
                            <option value="{{ $env }}" {{ request('gathering_environment') === $env ? 'selected' : '' }}>
                                {{ $env === 'road' ? '道路' : 'ダンジョン' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">アイテムカテゴリ</label>
                        <select name="item_category" class="admin-form-input">
                            <option value="">全てのカテゴリ</option>
                            @foreach($itemCategories as $category)
                            <option value="{{ $category }}" {{ request('item_category') === $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">状態</label>
                        <select name="is_active" class="admin-form-input">
                            <option value="">全ての状態</option>
                            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>アクティブ</option>
                            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>非アクティブ</option>
                        </select>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">&nbsp;</label>
                        <button type="submit" class="admin-btn admin-btn-primary">フィルタ適用</button>
                        <a href="{{ route('admin.gathering.index') }}" class="admin-btn admin-btn-secondary">リセット</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 採集設定一覧 -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>採集設定一覧</h3>
        </div>
        <div class="admin-card-body">
            @if($gatheringMappings->count() > 0)
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ルート</th>
                            <th>アイテム</th>
                            <th>必要スキルLv</th>
                            <th>成功率</th>
                            <th>数量範囲</th>
                            <th>状態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gatheringMappings as $mapping)
                        <tr>
                            <td>
                                <span class="admin-badge admin-badge-{{ $mapping->route->category === 'road' ? 'primary' : 'secondary' }}">
                                    {{ $mapping->route->name }}
                                </span>
                            </td>
                            <td>
                                <div class="item-info">
                                    <strong>{{ $mapping->item->name }}</strong>
                                    <small class="text-muted">{{ $mapping->item->getCategoryName() }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="admin-badge admin-badge-info">
                                    Lv.{{ $mapping->required_skill_level }}
                                </span>
                            </td>
                            <td>
                                <div class="success-rate">
                                    <span class="rate-value">{{ $mapping->success_rate }}%</span>
                                    <div class="rate-bar">
                                        <div class="rate-fill" style="width: {{ $mapping->success_rate }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $mapping->quantity_min }}-{{ $mapping->quantity_max }}</td>
                            <td>
                                <span class="admin-badge admin-badge-{{ $mapping->is_active ? 'success' : 'danger' }}">
                                    {{ $mapping->is_active ? 'アクティブ' : '非アクティブ' }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-action-buttons">
                                    @if(auth()->user()->can('gathering.edit'))
                                    <a href="{{ route('admin.gathering.edit', $mapping) }}" class="admin-btn admin-btn-sm admin-btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    @if(auth()->user()->can('gathering.delete'))
                                    <form method="POST" action="{{ route('admin.gathering.destroy', $mapping) }}" style="display: inline;" onsubmit="return confirm('この採集設定を削除してもよろしいですか？')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="admin-empty-state">
                <div class="admin-empty-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>採集設定がありません</h3>
                <p>まだ採集設定が作成されていません。新しい採集設定を作成してください。</p>
                @if(auth()->user()->can('gathering.create'))
                <a href="{{ route('admin.gathering.create') }}" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> 最初の採集設定を作成
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// 採集管理画面専用JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // 成功率バーのアニメーション
    const rateBars = document.querySelectorAll('.rate-fill');
    rateBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
            bar.style.transition = 'width 0.5s ease-in-out';
        }, 100);
    });
});
</script>
@endsection
```

### 7. **既存GatheringController更新**

#### 新DB対応リファクタリング
```php
// app/Http/Controllers/GatheringController.php 更新

public function gather(Request $request): JsonResponse
{
    $user = Auth::user();
    $player = $user->getOrCreatePlayer();
    
    // Road・Dungeon採集対応チェック
    if (!in_array($player->location_type, ['road', 'dungeon'])) {
        return response()->json(['error' => '採集は道路またはダンジョンでのみ可能です。'], 400);
    }

    // 採集スキルをチェック
    if (!$player->hasSkill('採集')) {
        return response()->json(['error' => '採集スキルがありません。'], 400);
    }
    
    $gatheringSkill = $player->getSkill('採集');
    $spCost = $gatheringSkill->getSkillSpCost();

    if ($player->sp < $spCost) {
        return response()->json(['error' => 'SPが不足しています。'], 400);
    }

    try {
        // 新DB方式：GatheringMappingから採集可能アイテム取得
        $availableItems = GatheringMapping::where('route_id', $player->location_id)
            ->where('is_active', true)
            ->forSkillLevel($gatheringSkill->level)
            ->with('item')
            ->get();

        if ($availableItems->isEmpty()) {
            return response()->json(['error' => 'このエリアでは採集できるアイテムがありません。'], 400);
        }

        // 重み付きランダム選択
        $selectedMapping = $this->selectRandomMapping($availableItems);
        
        // 成功率計算（スキルレベルボーナス適用）
        $actualSuccessRate = $selectedMapping->calculateSuccessRate($gatheringSkill->level);
        $success = mt_rand(1, 100) <= $actualSuccessRate;

        if (!$success) {
            // SP消費
            $player->sp -= $spCost;
            $player->save();

            return response()->json([
                'success' => false,
                'message' => '採集に失敗しました...',
                'sp_consumed' => $spCost,
                'remaining_sp' => $player->sp,
            ]);
        }

        // 成功時の処理
        $quantity = mt_rand($selectedMapping->quantity_min, $selectedMapping->quantity_max);
        
        // インベントリに追加
        $player->addItemToInventory($selectedMapping->item->id, $quantity);
        
        // SP消費 & 経験値獲得
        $player->sp -= $spCost;
        $experienceGained = $this->calculateGatheringExperience($selectedMapping, $quantity);
        $gatheringSkill->addExperience($experienceGained);
        
        $player->save();

        return response()->json([
            'success' => true,
            'item_obtained' => [
                'name' => $selectedMapping->item->name,
                'quantity' => $quantity,
            ],
            'sp_consumed' => $spCost,
            'remaining_sp' => $player->sp,
            'experience_gained' => $experienceGained,
            'skill_level' => $gatheringSkill->level,
        ]);

    } catch (\Exception $e) {
        Log::error('Gathering failed', [
            'player_id' => $player->id,
            'location_id' => $player->location_id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'error' => '採集処理中にエラーが発生しました。'
        ], 500);
    }
}

public function getGatheringInfo(Request $request): JsonResponse
{
    $user = Auth::user();
    $player = $user->getOrCreatePlayer();
    
    if (!in_array($player->location_type, ['road', 'dungeon'])) {
        return response()->json(['error' => '採集情報は道路またはダンジョンでのみ確認できます。'], 400);
    }

    $gatheringSkill = $player->getSkill('採集');
    
    if (!$gatheringSkill) {
        return response()->json(['error' => '採集スキルがありません。'], 400);
    }

    // 新DB方式：全採集アイテム取得
    $allMappings = GatheringMapping::where('route_id', $player->location_id)
        ->where('is_active', true)
        ->with('item')
        ->orderBy('required_skill_level')
        ->get();

    $itemsWithStatus = $allMappings->map(function($mapping) use ($gatheringSkill) {
        $canGather = $mapping->required_skill_level <= $gatheringSkill->level;
        $actualSuccessRate = $canGather ? $mapping->calculateSuccessRate($gatheringSkill->level) : 0;
        
        return [
            'item_name' => $mapping->item->name,
            'required_skill_level' => $mapping->required_skill_level,
            'base_success_rate' => $mapping->success_rate,
            'actual_success_rate' => $actualSuccessRate,
            'quantity_range' => $mapping->quantity_min . '-' . $mapping->quantity_max,
            'can_gather' => $canGather,
        ];
    });

    $currentLocation = Route::find($player->location_id);

    return response()->json([
        'skill_level' => $gatheringSkill->level,
        'experience' => $gatheringSkill->experience,
        'sp_cost' => $gatheringSkill->sp_cost,
        'current_sp' => $player->sp,
        'can_gather' => $player->sp >= $gatheringSkill->sp_cost,
        'road_name' => $currentLocation?->name ?? '不明なエリア',
        'all_items' => $itemsWithStatus,
        'available_items_count' => $itemsWithStatus->where('can_gather', true)->count(),
    ]);
}

/**
 * ランダム選択（シンプル版）
 */
private function selectRandomMapping($mappings): GatheringMapping
{
    return $mappings->random();
}

/**
 * 採集経験値計算（シンプル版）
 */
private function calculateGatheringExperience(GatheringMapping $mapping, int $quantity): int
{
    $baseExp = $mapping->required_skill_level * 2;
    $quantityBonus = $quantity - 1;
    
    return $baseExp + $quantityBonus;
}
```

---

## 🚀 実装ステップ

### Phase 1: 基盤構築（1-2日）
1. **マイグレーション作成・実行**
   - `gathering_mappings` テーブル作成
   - インデックス最適化
   
2. **モデル・リレーション実装**
   - `GatheringMapping` モデル作成
   - `Route` モデル拡張
   - リレーション設定

3. **サービスクラス実装**
   - `AdminGatheringService` 作成
   - 基本CRUD操作実装

### Phase 2: 管理画面実装（2-3日）
1. **コントローラー実装**
   - `AdminGatheringController` 作成
   - AdminController基底クラス継承
   - 権限チェック統合

2. **ルーティング設定**
   - 権限グループ化
   - RESTful設計適用

3. **ビューテンプレート作成**
   - 統一デザインシステム適用
   - レスポンシブ対応
   - ナビゲーション統合

### Phase 3: ゲームロジック更新（1-2日）
1. **GatheringController リファクタリング**
   - DB駆動ロジックへ移行
   - 既存API互換性維持
   - エラーハンドリング強化

2. **データ移行処理**
   - 既存ハードコードデータのDB移行
   - バリデーション・整合性チェック

### Phase 4: テスト・最適化（1-2日）
1. **機能テスト**
   - 権限テスト
   - CRUD操作テスト
   - ゲームロジック整合性テスト

2. **パフォーマンス最適化**
   - クエリ最適化
   - キャッシュ戦略実装
   - インデックス調整

3. **UI/UXテスト**
   - ユーザビリティテスト
   - レスポンシブテスト
   - アクセシビリティ確認

---

## 🎯 ベストプラクティス

### 📋 セキュリティ
- **権限チェック**: 全メソッドで `$this->checkPermission()` 実行
- **監査ログ**: 重要操作の完全記録
- **入力検証**: Laravel Validation による厳密なチェック
- **SQLインジェクション対策**: Eloquent ORM 使用

### 🔧 パフォーマンス
- **Eager Loading**: N+1クエリ問題の回避
- **インデックス最適化**: 検索・フィルタ性能向上
- **キャッシュ活用**: 統計データの適切なキャッシュ
- **ページネーション**: 大量データ対応

### 🎨 UI/UX
- **統一デザイン**: デザインシステム変数の完全活用
- **レスポンシブ**: モバイル対応
- **フィードバック**: 操作結果の明確な表示
- **アクセシビリティ**: スクリーンリーダー対応

### 🚀 拡張性
- **モジュラー設計**: 機能単位での分離
- **設定駆動**: ハードコード値の排除
- **API設計**: 将来のフロントエンド分離対応
- **ドキュメント**: 保守性向上のための詳細記録

---

## ✅ 完了基準

### 機能要件
- [ ] `gathering_mappings` テーブル作成・マイグレーション完了
- [ ] Admin管理画面での採集設定CRUD操作可能
- [ ] Routes-GatheringMapping の1対1紐付け機能実装
- [ ] 既存ゲームロジックのDB駆動への完全移行
- [ ] レファレンスドキュメント準拠の実装

### 技術要件
- [ ] AdminController基底クラス継承
- [ ] 権限ベースアクセス制御統合
- [ ] 監査ログシステム連携
- [ ] 統一デザインシステム適用
- [ ] エラーハンドリング・バリデーション実装

### 品質要件
- [ ] セキュリティテスト合格
- [ ] パフォーマンステスト合格
- [ ] UI/UXテスト合格
- [ ] ドキュメント整備完了

---

**作成者**: GitHub Copilot  
**最終更新**: 2025年8月25日  
**バージョン**: 1.0  
**推定実装期間**: 6-9日  
**優先度**: High  
**複雑度**: Medium-High  
