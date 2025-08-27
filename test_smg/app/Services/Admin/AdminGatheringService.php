<?php

namespace App\Services\Admin;

use App\Models\GatheringMapping;
use App\Models\Route;
use App\Models\Item;
use App\Models\GatheringTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminGatheringService
{
    /**
     * フィルタ付き採集マッピング一覧取得
     */
    public function getGatheringMappings(array $filters = []): Collection
    {
        $query = GatheringMapping::with(['route', 'item']);
        
        // ルートIDフィルタ
        if (!empty($filters['route_id'])) {
            $query->where('route_id', $filters['route_id']);
        }
        
        // アイテムカテゴリフィルタ
        if (!empty($filters['item_category'])) {
            $query->whereHas('item', function($q) use ($filters) {
                $q->where('category', $filters['item_category']);
            });
        }
        
        // スキルレベルフィルタ
        if (isset($filters['skill_level']) && $filters['skill_level'] !== '') {
            $query->where('required_skill_level', '<=', $filters['skill_level']);
        }
        
        // アクティブ状態フィルタ
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        // 採集環境フィルタ（Road/Dungeon）
        if (!empty($filters['gathering_environment'])) {
            $query->whereHas('route', function($q) use ($filters) {
                $q->where('category', $filters['gathering_environment']);
            });
        }
        
        return $query->orderBy('route_id')
                    ->orderBy('required_skill_level')
                    ->get();
    }

    /**
     * 環境別採集統計を取得
     */
    public function getGatheringStatsByEnvironment(): array
    {
        return Route::whereIn('category', ['road', 'dungeon'])
            ->withCount(['allGatheringMappings as total_items'])
            ->withCount(['gatheringMappings as active_items'])
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
                            'environment' => $route->category,
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
     * ルート別採集統計を取得
     */
    public function getGatheringStatsByRoute(): array
    {
        $environmentStats = $this->getGatheringStatsByEnvironment();
        
        $routes = [];
        foreach ($environmentStats as $envStat) {
            $routes = array_merge($routes, $envStat['routes']);
        }
        
        return $routes;
    }

    /**
     * 採集マッピング作成
     */
    public function createGatheringMapping(array $data): GatheringMapping
    {
        // 整合性チェック
        $validationErrors = $this->validateGatheringData($data);
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('データ検証エラー: ' . implode(', ', $validationErrors));
        }

        try {
            return DB::transaction(function() use ($data) {
                return GatheringMapping::create($data);
            });
        } catch (\Exception $e) {
            Log::error('採集マッピング作成失敗', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 採集マッピング更新
     */
    public function updateGatheringMapping(GatheringMapping $mapping, array $data): GatheringMapping
    {
        // 既存データをマージして整合性チェック
        $mergedData = array_merge($mapping->toArray(), $data);
        $validationErrors = $this->validateGatheringData($mergedData, $mapping->id);
        
        if (!empty($validationErrors)) {
            throw new \InvalidArgumentException('データ検証エラー: ' . implode(', ', $validationErrors));
        }

        try {
            return DB::transaction(function() use ($mapping, $data) {
                $mapping->update($data);
                return $mapping->fresh();
            });
        } catch (\Exception $e) {
            Log::error('採集マッピング更新失敗', [
                'mapping_id' => $mapping->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 採集マッピング削除
     */
    public function deleteGatheringMapping(GatheringMapping $mapping): bool
    {
        try {
            return DB::transaction(function() use ($mapping) {
                return $mapping->delete();
            });
        } catch (\Exception $e) {
            Log::error('採集マッピング削除失敗', [
                'mapping_id' => $mapping->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 採集マッピングの有効/無効切り替え
     */
    public function toggleGatheringMapping(GatheringMapping $mapping): GatheringMapping
    {
        return $this->updateGatheringMapping($mapping, [
            'is_active' => !$mapping->is_active
        ]);
    }

    /**
     * 既存GatheringTableからのデータ移行
     */
    public function migrateFromGatheringTable(): array
    {
        $migrated = 0;
        $errors = [];
        
        // 既存のハードコードデータを取得
        $roadIds = ['road_1', 'road_2', 'road_3'];
        
        DB::beginTransaction();
        
        try {
            foreach ($roadIds as $roadId) {
                $gatheringData = GatheringTable::getGatheringTableByRoad($roadId);
                
                foreach ($gatheringData as $itemData) {
                    // アイテム名からアイテムIDを検索
                    $item = Item::findSampleItem($itemData['item_name']);
                    
                    if (!$item) {
                        $errors[] = "アイテム '{$itemData['item_name']}' が見つかりません（{$roadId}）";
                        continue;
                    }

                    // マッピングデータを作成
                    $mappingData = [
                        'route_id' => $roadId,
                        'item_id' => $item->id,
                        'required_skill_level' => $itemData['required_skill_level'],
                        'success_rate' => $itemData['success_rate'],
                        'quantity_min' => $itemData['quantity_min'],
                        'quantity_max' => $itemData['quantity_max'],
                        'is_active' => true,
                    ];

                    // 重複チェック
                    $existing = GatheringMapping::where('route_id', $roadId)
                                               ->where('item_id', $item->id)
                                               ->first();
                    
                    if (!$existing) {
                        GatheringMapping::create($mappingData);
                        $migrated++;
                    }
                }
            }
            
            DB::commit();
            
            Log::info('GatheringTable移行完了', [
                'migrated_count' => $migrated,
                'errors_count' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = 'データ移行中にエラーが発生しました: ' . $e->getMessage();
            
            Log::error('GatheringTable移行失敗', [
                'error' => $e->getMessage(),
                'migrated_before_error' => $migrated
            ]);
        }
        
        return [
            'migrated_count' => $migrated,
            'errors' => $errors,
        ];
    }

    /**
     * 採集データの整合性検証
     */
    public function validateGatheringData(array $data, ?int $excludeMappingId = null): array
    {
        $errors = [];
        
        // ルート存在チェック
        if (!empty($data['route_id'])) {
            $route = Route::find($data['route_id']);
            if (!$route) {
                $errors[] = 'ルートが存在しません';
                return $errors;
            }
            
            // RoadかDungeonのみ採集可能
            if (!in_array($route->category, ['road', 'dungeon'])) {
                $errors[] = '採集はRoadまたはDungeonでのみ可能です';
            }
        }

        // アイテム存在チェック
        if (!empty($data['item_id'])) {
            $item = Item::find($data['item_id']);
            if (!$item) {
                $errors[] = 'アイテムが存在しません';
            }
        }

        // 数値範囲チェック
        if (isset($data['required_skill_level'])) {
            if ($data['required_skill_level'] < 1 || $data['required_skill_level'] > 100) {
                $errors[] = '必要スキルレベルは1-100の範囲で設定してください';
            }
        }

        if (isset($data['success_rate'])) {
            if ($data['success_rate'] < 1 || $data['success_rate'] > 100) {
                $errors[] = '成功率は1-100の範囲で設定してください';
            }
        }

        if (isset($data['quantity_min']) && isset($data['quantity_max'])) {
            if ($data['quantity_min'] < 1 || $data['quantity_max'] < 1) {
                $errors[] = '数量は1以上で設定してください';
            }
            if ($data['quantity_min'] > $data['quantity_max']) {
                $errors[] = '最小数量は最大数量以下で設定してください';
            }
        }

        // 重複チェック
        if (!empty($data['route_id']) && !empty($data['item_id'])) {
            $query = GatheringMapping::where('route_id', $data['route_id'])
                                     ->where('item_id', $data['item_id']);
            
            if ($excludeMappingId) {
                $query->where('id', '!=', $excludeMappingId);
            }
            
            if ($query->exists()) {
                $errors[] = 'このルート・アイテムの組み合わせは既に存在します';
            }
        }
        
        return $errors;
    }

    /**
     * 採集可能ルート一覧取得
     */
    public function getGatheringEligibleRoutes(): Collection
    {
        return Route::whereIn('category', ['road', 'dungeon'])
                   ->active()
                   ->orderBy('category')
                   ->orderBy('name')
                   ->get();
    }

    /**
     * アイテムカテゴリ一覧取得
     */
    public function getItemCategories(): Collection
    {
        return Item::select('category')
                   ->distinct()
                   ->whereNotNull('category')
                   ->orderBy('category')
                   ->pluck('category');
    }

    /**
     * 採集システム統計サマリー
     */
    public function getSystemSummary(): array
    {
        $totalMappings = GatheringMapping::count();
        $activeMappings = GatheringMapping::where('is_active', true)->count();
        $gatheringRoutes = Route::whereIn('category', ['road', 'dungeon'])
                                ->whereHas('gatheringMappings')
                                ->count();
        $totalGatheringEligibleRoutes = Route::whereIn('category', ['road', 'dungeon'])->count();

        return [
            'total_mappings' => $totalMappings,
            'active_mappings' => $activeMappings,
            'inactive_mappings' => $totalMappings - $activeMappings,
            'gathering_routes' => $gatheringRoutes,
            'total_gathering_eligible_routes' => $totalGatheringEligibleRoutes,
            'unused_routes' => $totalGatheringEligibleRoutes - $gatheringRoutes,
            'configuration_completion' => $totalGatheringEligibleRoutes > 0 
                ? round(($gatheringRoutes / $totalGatheringEligibleRoutes) * 100, 1) 
                : 0,
        ];
    }

    /**
     * 採集設定の完全性チェック
     */
    public function validateSystemConfiguration(): array
    {
        $issues = [];
        
        // 採集対象ルートで設定がないものをチェック
        $unConfiguredRoutes = Route::whereIn('category', ['road', 'dungeon'])
                                  ->doesntHave('allGatheringMappings')
                                  ->get();
        
        if ($unConfiguredRoutes->count() > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => '採集設定がないルートがあります',
                'details' => $unConfiguredRoutes->pluck('name', 'id')->toArray()
            ];
        }

        // アクティブな設定がないルートをチェック
        $inactiveRoutes = Route::whereIn('category', ['road', 'dungeon'])
                              ->whereHas('allGatheringMappings')
                              ->doesntHave('gatheringMappings')
                              ->get();
        
        if ($inactiveRoutes->count() > 0) {
            $issues[] = [
                'type' => 'error',
                'message' => 'アクティブな採集設定がないルートがあります',
                'details' => $inactiveRoutes->pluck('name', 'id')->toArray()
            ];
        }

        // 各ルートの設定検証
        $routesWithIssues = Route::whereIn('category', ['road', 'dungeon'])
                                 ->whereHas('allGatheringMappings')
                                 ->get()
                                 ->filter(function($route) {
                                     return !empty($route->validateGatheringConfiguration());
                                 });

        if ($routesWithIssues->count() > 0) {
            $routeIssues = [];
            foreach ($routesWithIssues as $route) {
                $routeIssues[$route->name] = $route->validateGatheringConfiguration();
            }
            
            $issues[] = [
                'type' => 'error',
                'message' => '採集設定に問題があるルートがあります',
                'details' => $routeIssues
            ];
        }

        return $issues;
    }
}