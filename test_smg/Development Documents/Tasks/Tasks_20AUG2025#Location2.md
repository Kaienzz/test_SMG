# ダンジョントグル管理機能実装タスク

## 概要
ダンジョン管理画面にトグル機能を実装し、各ダンジョンがどのLocationで構成されているかを視覚的に管理できるシステムを構築する。

## 前提条件
- `dungeon_descs`テーブルでダンジョン基本情報を管理
- `game_locations`テーブルの各レコードが特定のダンジョンに属することを`dungeon_id`で識別
- トグルUIで親子関係を分かりやすく表示

## 実装タスクリスト

### Phase 1: データベース設計・モデル実装

#### Task 1.1: dungeon_descsテーブル作成
**優先度**: 🔴 高
**実装時間**: 30分

```bash
php artisan make:migration create_dungeon_descs_table
```

**マイグレーション内容**:
```sql
Schema::create('dungeon_descs', function (Blueprint $table) {
    $table->string('dungeon_id')->primary();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('dungeon_type')->nullable(); // 'cave', 'ruins', 'tower', 'underground'
    $table->integer('total_floors')->nullable();
    $table->string('theme_color')->default('#6c757d'); // UI表示用カラー
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    
    $table->index(['dungeon_type', 'is_active']);
});
```

**成果物**: 
- `database/migrations/xxxx_create_dungeon_descs_table.php`

#### Task 1.2: game_locationsテーブルにdungeon_id追加
**優先度**: 🔴 高  
**実装時間**: 20分

```bash
php artisan make:migration add_dungeon_id_to_game_locations_table --table=game_locations
```

**マイグレーション内容**:
```sql
Schema::table('game_locations', function (Blueprint $table) {
    $table->string('dungeon_id')->nullable()->after('category');
    $table->integer('floor_number')->nullable()->after('dungeon_id'); // ダンジョン内での階層
    
    $table->foreign('dungeon_id')->references('dungeon_id')->on('dungeon_descs')->onDelete('set null');
    $table->index(['dungeon_id', 'floor_number']);
});
```

**成果物**: 
- `database/migrations/xxxx_add_dungeon_id_to_game_locations_table.php`

#### Task 1.3: DungeonDescモデル作成
**優先度**: 🔴 高
**実装時間**: 30分

```bash
php artisan make:model DungeonDesc
```

**モデル内容**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DungeonDesc extends Model
{
    protected $primaryKey = 'dungeon_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'dungeon_id',
        'name',
        'description',
        'dungeon_type',
        'total_floors',
        'theme_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function locations()
    {
        return $this->hasMany(GameLocation::class, 'dungeon_id', 'dungeon_id')
                   ->orderBy('floor_number');
    }

    public function activeLocations()
    {
        return $this->locations()->where('is_active', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('dungeon_type', $type);
    }

    // Accessors
    public function getLocationCountAttribute(): int
    {
        return $this->locations()->count();
    }

    public function getActiveLocationCountAttribute(): int
    {
        return $this->activeLocations()->count();
    }
}
```

**成果物**: 
- `app/Models/DungeonDesc.php`

#### Task 1.4: GameLocationモデルにダンジョンリレーション追加
**優先度**: 🔴 高
**実装時間**: 15分

**追加内容**:
```php
// app/Models/GameLocation.php に追加

/**
 * 所属するダンジョン情報
 */
public function dungeonDesc()
{
    return $this->belongsTo(DungeonDesc::class, 'dungeon_id', 'dungeon_id');
}

/**
 * ダンジョンフロア用スコープ
 */
public function scopeDungeonFloors($query)
{
    return $query->where('category', 'dungeon')
                 ->whereNotNull('dungeon_id');
}

/**
 * 特定ダンジョンのフロア
 */
public function scopeOfDungeon($query, string $dungeonId)
{
    return $query->where('dungeon_id', $dungeonId);
}

/**
 * ダンジョンフロア表示名
 */
public function getDungeonFloorNameAttribute(): string
{
    if ($this->dungeon_id && $this->floor_number) {
        return "{$this->dungeonDesc?->name} {$this->floor_number}F";
    }
    return $this->name;
}
```

**成果物**: 
- `app/Models/GameLocation.php` (更新)

### Phase 2: サービス層実装

#### Task 2.1: AdminLocationServiceにダンジョン管理機能追加
**優先度**: 🟡 中
**実装時間**: 60分

**追加メソッド**:
```php
// app/Services/Admin/AdminLocationService.php に追加

/**
 * ダンジョン一覧を取得（トグル用構造）
 */
public function getDungeonsWithLocations(array $filters = []): array
{
    try {
        $query = DungeonDesc::with([
            'locations' => function($q) {
                $q->orderBy('floor_number')->with('monsterSpawns');
            }
        ]);

        // フィルタリング
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('dungeon_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['dungeon_type'])) {
            $query->where('dungeon_type', $filters['dungeon_type']);
        }

        if (isset($filters['has_locations'])) {
            if ($filters['has_locations']) {
                $query->has('locations');
            } else {
                $query->doesntHave('locations');
            }
        }

        // ソート
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        $dungeons = $query->get();

        return $dungeons->map(function($dungeon) {
            $locations = $dungeon->locations->map(function($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->name,
                    'floor_number' => $location->floor_number,
                    'difficulty' => $location->difficulty,
                    'encounter_rate' => $location->encounter_rate,
                    'monster_spawns_count' => $location->monsterSpawns->count(),
                    'active_spawns_count' => $location->monsterSpawns->where('is_active', true)->count(),
                    'total_spawn_rate' => round($location->monsterSpawns->sum('spawn_rate'), 3),
                    'is_active' => $location->is_active,
                ];
            });

            return [
                'dungeon_id' => $dungeon->dungeon_id,
                'name' => $dungeon->name,
                'description' => $dungeon->description,
                'dungeon_type' => $dungeon->dungeon_type,
                'total_floors' => $dungeon->total_floors,
                'theme_color' => $dungeon->theme_color,
                'is_active' => $dungeon->is_active,
                'locations_count' => $locations->count(),
                'active_locations_count' => $locations->where('is_active', true)->count(),
                'total_monsters' => $locations->sum('monster_spawns_count'),
                'completion_rate' => $locations->count() > 0 ? 
                    round($locations->where('total_spawn_rate', '>=', 0.99)->count() / $locations->count() * 100, 1) : 0,
                'locations' => $locations->toArray(),
            ];
        })->toArray();

    } catch (\Exception $e) {
        Log::error('Failed to get dungeons with locations', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * ダンジョン統計情報を取得
 */
public function getDungeonStats(): array
{
    try {
        $totalDungeons = DungeonDesc::count();
        $activeDungeons = DungeonDesc::where('is_active', true)->count();
        $dungeonTypes = DungeonDesc::groupBy('dungeon_type')->pluck('dungeon_type')->filter()->toArray();
        $totalDungeonLocations = GameLocation::whereNotNull('dungeon_id')->count();
        $avgFloorsPerDungeon = DungeonDesc::avg('total_floors') ?? 0;

        return [
            'total_dungeons' => $totalDungeons,
            'active_dungeons' => $activeDungeons,
            'inactive_dungeons' => $totalDungeons - $activeDungeons,
            'dungeon_types' => $dungeonTypes,
            'total_dungeon_locations' => $totalDungeonLocations,
            'average_floors_per_dungeon' => round($avgFloorsPerDungeon, 1),
            'dungeons_with_monsters' => DungeonDesc::whereHas('locations.monsterSpawns')->count(),
        ];
    } catch (\Exception $e) {
        Log::error('Failed to get dungeon stats', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * ダンジョン詳細を取得
 */
public function getDungeonDetail(string $dungeonId): ?array
{
    try {
        $dungeon = DungeonDesc::with([
            'locations' => function($q) {
                $q->orderBy('floor_number')
                  ->with(['monsterSpawns.monster', 'sourceConnections.targetLocation']);
            }
        ])->find($dungeonId);

        if (!$dungeon) {
            return null;
        }

        $locations = $dungeon->locations->map(function($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'description' => $location->description,
                'floor_number' => $location->floor_number,
                'difficulty' => $location->difficulty,
                'encounter_rate' => $location->encounter_rate,
                'length' => $location->length,
                'monster_spawns' => $location->monsterSpawns->map(function($spawn) {
                    return [
                        'monster_name' => $spawn->monster?->name,
                        'monster_level' => $spawn->monster?->level,
                        'spawn_rate' => $spawn->spawn_rate,
                        'is_active' => $spawn->is_active,
                    ];
                })->toArray(),
                'connections_count' => $location->sourceConnections->count(),
                'is_active' => $location->is_active,
            ];
        });

        return [
            'dungeon_id' => $dungeon->dungeon_id,
            'name' => $dungeon->name,
            'description' => $dungeon->description,
            'dungeon_type' => $dungeon->dungeon_type,
            'total_floors' => $dungeon->total_floors,
            'theme_color' => $dungeon->theme_color,
            'is_active' => $dungeon->is_active,
            'locations' => $locations->toArray(),
            'stats' => [
                'total_locations' => $locations->count(),
                'active_locations' => $locations->where('is_active', true)->count(),
                'total_monsters' => $locations->sum(fn($l) => count($l['monster_spawns'])),
                'completed_floors' => $locations->filter(function($l) {
                    return collect($l['monster_spawns'])->sum('spawn_rate') >= 0.99;
                })->count(),
            ]
        ];

    } catch (\Exception $e) {
        Log::error('Failed to get dungeon detail', [
            'dungeon_id' => $dungeonId,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

**成果物**: 
- `app/Services/Admin/AdminLocationService.php` (更新)

### Phase 3: コントローラー実装

#### Task 3.1: AdminLocationControllerにダンジョン管理メソッド追加
**優先度**: 🟡 中
**実装時間**: 45分

**追加メソッド**:
```php
// app/Http/Controllers/Admin/AdminLocationController.php に追加

/**
 * ダンジョントグル管理画面
 */
public function dungeons(Request $request)
{
    $this->initializeForRequest();
    $this->checkPermission('locations.view');
    $this->trackPageAccess('locations.dungeons');

    $filters = $request->only(['search', 'dungeon_type', 'has_locations', 'sort_by', 'sort_direction']);
    
    try {
        $dungeonsWithLocations = $this->adminLocationService->getDungeonsWithLocations($filters);
        $dungeonStats = $this->adminLocationService->getDungeonStats();
        $dungeonTypes = ['cave', 'ruins', 'tower', 'underground', 'fortress'];

        $this->auditLog('locations.dungeons.viewed', [
            'filters' => $filters,
            'result_count' => count($dungeonsWithLocations)
        ]);

        return view('admin.locations.dungeons.index', compact(
            'dungeonsWithLocations',
            'dungeonStats',
            'dungeonTypes',
            'filters'
        ));

    } catch (\Exception $e) {
        Log::error('Failed to load dungeons data', [
            'error' => $e->getMessage()
        ]);
        
        return view('admin.locations.dungeons.index', [
            'error' => 'ダンジョンデータの読み込みに失敗しました: ' . $e->getMessage(),
            'dungeonsWithLocations' => [],
            'dungeonStats' => [],
            'dungeonTypes' => [],
            'filters' => $filters
        ]);
    }
}

/**
 * ダンジョン詳細表示
 */
public function dungeonDetail(Request $request, string $dungeonId)
{
    $this->initializeForRequest();
    $this->checkPermission('locations.view');

    try {
        $dungeonDetail = $this->adminLocationService->getDungeonDetail($dungeonId);

        if (!$dungeonDetail) {
            return redirect()->route('admin.locations.dungeons')
                           ->with('error', 'ダンジョンが見つかりませんでした。ID: ' . $dungeonId);
        }

        $this->auditLog('locations.dungeon_detail.viewed', [
            'dungeon_id' => $dungeonId,
            'dungeon_name' => $dungeonDetail['name']
        ]);

        return view('admin.locations.dungeons.show', compact('dungeonDetail'));

    } catch (\Exception $e) {
        Log::error('Failed to load dungeon detail', [
            'dungeon_id' => $dungeonId,
            'error' => $e->getMessage()
        ]);
        
        return redirect()->route('admin.locations.dungeons')
                       ->with('error', 'ダンジョン詳細の読み込みに失敗しました: ' . $e->getMessage());
    }
}
```

**成果物**: 
- `app/Http/Controllers/Admin/AdminLocationController.php` (更新)

### Phase 4: ビュー実装

#### Task 4.1: ダンジョントグル管理画面作成
**優先度**: 🟡 中
**実装時間**: 90分

**ファイルパス**: `resources/views/admin/locations/dungeons/index.blade.php`

**主要機能**:
- ダンジョン統計カード表示
- フィルター・検索機能
- トグル式ダンジョン一覧
- 各ダンジョン配下のLocation表示

**UI構成**:
```html
@extends('admin.layouts.app')

@section('title', 'ダンジョン管理')

@section('content')
<div class="container-fluid">
    <!-- ページヘッダー -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">ダンジョン管理</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.locations.index') }}">ロケーション管理</a></li>
                    <li class="breadcrumb-item active">ダンジョン管理</li>
                </ol>
            </nav>
        </div>
        @if($canManageGameData ?? false)
        <div class="btn-group">
            <a href="{{ route('admin.dungeons.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新しいダンジョンを追加
            </a>
            <a href="{{ route('admin.locations.create') }}" class="btn btn-outline-primary">
                <i class="fas fa-plus"></i> フロアを追加
            </a>
        </div>
        @endif
    </div>

    <!-- 統計カード -->
    @if(isset($dungeonStats))
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">総ダンジョン数</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dungeonStats['total_dungeons'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dungeon fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">アクティブダンジョン</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dungeonStats['active_dungeons'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">総フロア数</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dungeonStats['total_dungeon_locations'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">平均フロア数</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $dungeonStats['average_floors_per_dungeon'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- フィルター・検索フォーム -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> フィルター・検索
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.locations.dungeons') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="search" class="form-label">キーワード検索</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ $filters['search'] ?? '' }}" 
                               placeholder="ダンジョン名、ID、説明で検索">
                    </div>
                    <div class="col-md-2">
                        <label for="dungeon_type" class="form-label">ダンジョンタイプ</label>
                        <select class="form-select" id="dungeon_type" name="dungeon_type">
                            <option value="">全て</option>
                            @foreach($dungeonTypes as $type)
                                @php
                                    $typeLabels = [
                                        'cave' => '洞窟', 'ruins' => '遺跡', 'tower' => '塔', 
                                        'underground' => '地下', 'fortress' => '要塞'
                                    ];
                                @endphp
                                <option value="{{ $type }}" {{ ($filters['dungeon_type'] ?? '') == $type ? 'selected' : '' }}>
                                    {{ $typeLabels[$type] ?? $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="has_locations" class="form-label">フロア状況</label>
                        <select class="form-select" id="has_locations" name="has_locations">
                            <option value="">全て</option>
                            <option value="1" {{ ($filters['has_locations'] ?? '') == '1' ? 'selected' : '' }}>フロア有り</option>
                            <option value="0" {{ ($filters['has_locations'] ?? '') == '0' ? 'selected' : '' }}>フロア無し</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sort_by" class="form-label">ソート項目</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="name" {{ ($filters['sort_by'] ?? 'name') == 'name' ? 'selected' : '' }}>名前</option>
                            <option value="dungeon_type" {{ ($filters['sort_by'] ?? '') == 'dungeon_type' ? 'selected' : '' }}>タイプ</option>
                            <option value="total_floors" {{ ($filters['sort_by'] ?? '') == 'total_floors' ? 'selected' : '' }}>フロア数</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> 検索
                        </button>
                        <a href="{{ route('admin.locations.dungeons') }}" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-sync-alt"></i> リセット
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="toggleAllDungeons()">
                            <i class="fas fa-expand-arrows-alt"></i> 全て展開
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ダンジョンリスト（トグル式） -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> ダンジョン一覧
            </h6>
            <small class="text-muted">
                {{ count($dungeonsWithLocations) }}個のダンジョン
            </small>
        </div>
        <div class="card-body">
            @if(count($dungeonsWithLocations) > 0)
                <div class="accordion" id="dungeonAccordion">
                    @foreach($dungeonsWithLocations as $index => $dungeon)
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading{{ $index }}">
                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse{{ $index }}" 
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                    aria-controls="collapse{{ $index }}">
                                
                                <!-- ダンジョン基本情報 -->
                                <div class="w-100 d-flex justify-content-between align-items-center me-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <span class="badge" style="background-color: {{ $dungeon['theme_color'] }};">
                                                {{ $dungeon['dungeon_id'] }}
                                            </span>
                                        </div>
                                        <div class="me-3">
                                            <h6 class="mb-1 fw-bold">{{ $dungeon['name'] }}</h6>
                                            @if($dungeon['dungeon_type'])
                                                @php
                                                    $typeLabels = [
                                                        'cave' => '洞窟', 'ruins' => '遺跡', 'tower' => '塔', 
                                                        'underground' => '地下', 'fortress' => '要塞'
                                                    ];
                                                @endphp
                                                <small class="text-muted">
                                                    {{ $typeLabels[$dungeon['dungeon_type']] ?? $dungeon['dungeon_type'] }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 text-center">
                                            <small class="text-muted d-block">フロア数</small>
                                            <span class="badge bg-info">{{ $dungeon['locations_count'] }}</span>
                                        </div>
                                        <div class="me-3 text-center">
                                            <small class="text-muted d-block">モンスター</small>
                                            <span class="badge bg-success">{{ $dungeon['total_monsters'] }}</span>
                                        </div>
                                        <div class="me-3 text-center">
                                            <small class="text-muted d-block">完成度</small>
                                            <span class="badge bg-{{ $dungeon['completion_rate'] >= 80 ? 'success' : ($dungeon['completion_rate'] >= 50 ? 'warning' : 'danger') }}">
                                                {{ $dungeon['completion_rate'] }}%
                                            </span>
                                        </div>
                                        @if(!$dungeon['is_active'])
                                            <span class="badge bg-secondary">非アクティブ</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse{{ $index }}" 
                             class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                             aria-labelledby="heading{{ $index }}" 
                             data-bs-parent="#dungeonAccordion">
                            <div class="accordion-body">
                                <!-- ダンジョン説明 -->
                                @if($dungeon['description'])
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    {{ $dungeon['description'] }}
                                </div>
                                @endif

                                <!-- フロア一覧テーブル -->
                                @if(count($dungeon['locations']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="80">フロア</th>
                                                <th>フロア名</th>
                                                <th width="100">難易度</th>
                                                <th width="120">エンカウント率</th>
                                                <th width="100">モンスター</th>
                                                <th width="120">スポーン完成度</th>
                                                <th width="80">状態</th>
                                                @if($canManageGameData ?? false)
                                                <th width="150">操作</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dungeon['locations'] as $location)
                                            <tr class="{{ !$location['is_active'] ? 'table-secondary' : '' }}">
                                                <td class="text-center">
                                                    <span class="badge" style="background-color: {{ $dungeon['theme_color'] }};">
                                                        {{ $location['floor_number'] ?? '?' }}F
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>{{ $location['name'] }}</strong>
                                                    <br><small class="text-muted">ID: {{ $location['id'] }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if($location['difficulty'])
                                                        @php
                                                            $difficultyClass = [
                                                                'easy' => 'success', 'normal' => 'info', 
                                                                'hard' => 'warning', 'extreme' => 'danger'
                                                            ][$location['difficulty']] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge bg-{{ $difficultyClass }}">
                                                            {{ $location['difficulty'] }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    {{ number_format($location['encounter_rate'] * 100, 1) }}%
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $location['monster_spawns_count'] > 0 ? 'success' : 'secondary' }}">
                                                        {{ $location['monster_spawns_count'] }}種類
                                                    </span>
                                                    @if($location['active_spawns_count'] !== $location['monster_spawns_count'])
                                                        <br><small class="text-warning">
                                                            ({{ $location['active_spawns_count'] }}アクティブ)
                                                        </small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $spawnRate = $location['total_spawn_rate'];
                                                        $completionClass = $spawnRate >= 0.99 ? 'success' : 
                                                                          ($spawnRate >= 0.5 ? 'warning' : 'danger');
                                                    @endphp
                                                    <span class="badge bg-{{ $completionClass }}">
                                                        {{ number_format($spawnRate * 100, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-{{ $location['is_active'] ? 'success' : 'secondary' }}">
                                                        {{ $location['is_active'] ? 'アクティブ' : '非アクティブ' }}
                                                    </span>
                                                </td>
                                                @if($canManageGameData ?? false)
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('admin.locations.show', $location['id']) }}" 
                                                           class="btn btn-outline-info" title="詳細">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('admin.locations.edit', $location['id']) }}" 
                                                           class="btn btn-outline-primary" title="編集">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger" 
                                                                onclick="deleteLocation('{{ $location['id'] }}', '{{ $location['name'] }}')" 
                                                                title="削除">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                @endif
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-4">
                                    <i class="fas fa-layer-group fa-3x text-gray-300 mb-3"></i>
                                    <h6 class="text-muted">このダンジョンにはまだフロアが設定されていません</h6>
                                    @if($canManageGameData ?? false)
                                        <a href="{{ route('admin.locations.create', ['dungeon_id' => $dungeon['dungeon_id']]) }}" 
                                           class="btn btn-primary mt-2">
                                            <i class="fas fa-plus"></i> フロアを追加
                                        </a>
                                    @endif
                                </div>
                                @endif

                                <!-- ダンジョン操作ボタン -->
                                @if($canManageGameData ?? false)
                                <div class="mt-3 pt-3 border-top">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.dungeons.edit', $dungeon['dungeon_id']) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i> ダンジョン編集
                                        </a>
                                        <a href="{{ route('admin.locations.dungeons.show', $dungeon['dungeon_id']) }}" 
                                           class="btn btn-outline-info">
                                            <i class="fas fa-eye"></i> 詳細表示
                                        </a>
                                        <button type="button" 
                                                class="btn btn-outline-danger" 
                                                onclick="deleteDungeon('{{ $dungeon['dungeon_id'] }}', '{{ $dungeon['name'] }}')">
                                            <i class="fas fa-trash"></i> ダンジョン削除
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-dungeon fa-3x text-gray-300 mb-3"></i>
                <h6 class="text-muted">該当するダンジョンが見つかりません</h6>
                @if($canManageGameData ?? false)
                    <a href="{{ route('admin.dungeons.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> 最初のダンジョンを作成
                    </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleAllDungeons() {
    const accordionButtons = document.querySelectorAll('#dungeonAccordion .accordion-button');
    const collapses = document.querySelectorAll('#dungeonAccordion .accordion-collapse');
    
    let allExpanded = true;
    collapses.forEach(collapse => {
        if (!collapse.classList.contains('show')) {
            allExpanded = false;
        }
    });
    
    if (allExpanded) {
        // 全て閉じる
        accordionButtons.forEach(button => {
            if (!button.classList.contains('collapsed')) {
                button.click();
            }
        });
    } else {
        // 全て開く
        accordionButtons.forEach(button => {
            if (button.classList.contains('collapsed')) {
                button.click();
            }
        });
    }
}

function deleteLocation(locationId, locationName) {
    if (confirm(`フロア「${locationName}」を削除しますか？\n\n警告: この操作は元に戻せません。`)) {
        // 削除処理の実装
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/locations/${locationId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteDungeon(dungeonId, dungeonName) {
    if (confirm(`ダンジョン「${dungeonName}」を削除しますか？\n\n警告: この操作は元に戻せません。関連するフロアも影響を受ける可能性があります。`)) {
        // 削除処理の実装
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/dungeons/${dungeonId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
```

**成果物**: 
- `resources/views/admin/locations/dungeons/index.blade.php`

#### Task 4.2: ダンジョン詳細画面作成
**優先度**: 🟢 低
**実装時間**: 60分

**ファイルパス**: `resources/views/admin/locations/dungeons/show.blade.php`

**成果物**: 
- `resources/views/admin/locations/dungeons/show.blade.php`

### Phase 5: ルート・権限設定

#### Task 5.1: ルート追加
**優先度**: 🔴 高
**実装時間**: 15分

**追加ルート**:
```php
// routes/admin.php に追加
Route::prefix('locations')->name('admin.locations.')->group(function () {
    Route::get('/dungeons', [AdminLocationController::class, 'dungeons'])->name('dungeons');
    Route::get('/dungeons/{dungeonId}', [AdminLocationController::class, 'dungeonDetail'])->name('dungeons.show');
});

Route::prefix('dungeons')->name('admin.dungeons.')->group(function () {
    Route::get('/', [AdminDungeonController::class, 'index'])->name('index');
    Route::get('/create', [AdminDungeonController::class, 'create'])->name('create');
    Route::post('/', [AdminDungeonController::class, 'store'])->name('store');
    Route::get('/{dungeonId}/edit', [AdminDungeonController::class, 'edit'])->name('edit');
    Route::put('/{dungeonId}', [AdminDungeonController::class, 'update'])->name('update');
    Route::delete('/{dungeonId}', [AdminDungeonController::class, 'destroy'])->name('destroy');
});
```

**成果物**: 
- `routes/admin.php` (更新)

### Phase 6: マイグレーション・シーダー実行

#### Task 6.1: マイグレーション実行
**優先度**: 🔴 高
**実装時間**: 10分

```bash
php artisan migrate
```

#### Task 6.2: サンプルデータ作成
**優先度**: 🟡 中
**実装時間**: 30分

**DungeonDescSeeder作成**:
```bash
php artisan make:seeder DungeonDescSeeder
```

**サンプルデータ**:
```php
<?php

use Illuminate\Database\Seeder;
use App\Models\DungeonDesc;
use App\Models\GameLocation;

class DungeonDescSeeder extends Seeder
{
    public function run()
    {
        // サンプルダンジョン作成
        $dungeons = [
            [
                'dungeon_id' => 'beginners_cave',
                'name' => '初心者の洞窟',
                'description' => '初心者向けの浅い洞窟。弱いモンスターが出現する。',
                'dungeon_type' => 'cave',
                'total_floors' => 3,
                'theme_color' => '#6c757d',
            ],
            [
                'dungeon_id' => 'ancient_ruins',
                'name' => '古代遺跡',
                'description' => '古代の文明の謎に満ちた遺跡。貴重なアイテムが眠っている。',
                'dungeon_type' => 'ruins',
                'total_floors' => 5,
                'theme_color' => '#ffc107',
            ],
            [
                'dungeon_id' => 'mystic_tower',
                'name' => '神秘の塔',
                'description' => '魔法使いが建てた高い塔。上層部には強力なモンスターが住む。',
                'dungeon_type' => 'tower',
                'total_floors' => 10,
                'theme_color' => '#6f42c1',
            ],
        ];

        foreach ($dungeons as $dungeonData) {
            DungeonDesc::create($dungeonData);
        }

        // 既存のダンジョンカテゴリのLocationにdungeon_idを設定
        $dungeonLocations = GameLocation::where('category', 'dungeon')->get();
        
        if ($dungeonLocations->count() > 0) {
            // 簡単な例：最初の3つを初心者の洞窟に割り当て
            $dungeonLocations->take(3)->each(function($location, $index) {
                $location->update([
                    'dungeon_id' => 'beginners_cave',
                    'floor_number' => $index + 1
                ]);
            });
        }
    }
}
```

**成果物**: 
- `database/seeders/DungeonDescSeeder.php`

## 実装スケジュール

### Week 1 (Phase 1-2)
- **Day 1-2**: データベース設計・マイグレーション・モデル作成
- **Day 3-5**: サービス層実装

### Week 2 (Phase 3-4)
- **Day 1-2**: コントローラー実装
- **Day 3-5**: ビュー実装（メインのトグルUI）

### Week 3 (Phase 5-6)
- **Day 1-2**: ルート設定・権限設定
- **Day 3**: マイグレーション実行・テスト
- **Day 4-5**: サンプルデータ作成・最終調整

## テスト項目

### 機能テスト
- [ ] ダンジョン一覧表示
- [ ] トグル展開・収納
- [ ] フィルタリング機能
- [ ] フロア一覧表示
- [ ] ダンジョン作成・編集・削除
- [ ] フロア作成・編集・削除

### パフォーマンステスト
- [ ] 大量ダンジョン（100個以上）での表示速度
- [ ] トグル操作のレスポンス時間
- [ ] フィルタリング処理時間

### UI/UXテスト
- [ ] モバイル対応
- [ ] アクセシビリティ
- [ ] 直感的な操作性

## 成果物

### コードファイル
- `database/migrations/xxxx_create_dungeon_descs_table.php`
- `database/migrations/xxxx_add_dungeon_id_to_game_locations_table.php`
- `app/Models/DungeonDesc.php`
- `app/Models/GameLocation.php` (更新)
- `app/Services/Admin/AdminLocationService.php` (更新)
- `app/Http/Controllers/Admin/AdminLocationController.php` (更新)
- `resources/views/admin/locations/dungeons/index.blade.php`
- `resources/views/admin/locations/dungeons/show.blade.php`
- `routes/admin.php` (更新)
- `database/seeders/DungeonDescSeeder.php`

### ドキュメント
- API仕様書（ダンジョン管理機能）
- UI設計書
- テスト仕様書

この実装により、ダンジョン管理がより直感的で効率的になり、各ダンジョンを構成するLocationの関係性が明確に可視化されます。