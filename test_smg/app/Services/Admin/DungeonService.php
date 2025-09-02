<?php

namespace App\Services\Admin;

use App\Models\DungeonDesc;
use App\Models\Route;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DungeonService
{
    /**
     * フロア検索（アタッチ候補用）
     */
    public function searchCandidateFloors(
        string $dungeonId,
        string $searchQuery = '',
        bool $onlyOrphans = true,
        int $perPage = 10
    ) {
        $query = Route::where('category', 'dungeon')
                     ->where('id', '!=', $dungeonId); // 自分以外
        
        if ($onlyOrphans) {
            $query->whereNull('dungeon_id');
        } else {
            // 他の親に紐づいているフロアも含める
            $query->where(function($q) use ($dungeonId) {
                $q->whereNull('dungeon_id')
                  ->orWhere('dungeon_id', '!=', $dungeonId);
            });
        }

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('name', 'LIKE', '%' . $searchQuery . '%')
                  ->orWhere('id', 'LIKE', '%' . $searchQuery . '%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * フロアを一括アタッチ
     */
    public function attachFloorsToParent(string $parentDungeonId, array $floorIds): array
    {
        try {
            return DB::transaction(function () use ($parentDungeonId, $floorIds) {
                $parent = DungeonDesc::where('dungeon_id', $parentDungeonId)->first();
                if (!$parent) {
                    throw new \Exception('指定されたダンジョンが見つかりません。');
                }

                // 対象フロアの検証
                $floors = Route::whereIn('id', $floorIds)
                              ->where('category', 'dungeon')
                              ->get();

                if ($floors->count() !== count($floorIds)) {
                    throw new \Exception('指定されたフロアの一部が見つからないか、無効です。');
                }

                // 現在の状態を記録（ロールバック用）
                $originalStates = [];
                foreach ($floors as $floor) {
                    $originalStates[$floor->id] = $floor->dungeon_id;
                }

                // 一括更新実行
                $updatedCount = Route::whereIn('id', $floorIds)
                                   ->where('category', 'dungeon')
                                   ->update(['dungeon_id' => $parent->dungeon_id]);

                return [
                    'success' => true,
                    'updated_count' => $updatedCount,
                    'parent' => $parent,
                    'floors' => $floors,
                    'original_states' => $originalStates
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to attach floors to parent', [
                'parent_dungeon_id' => $parentDungeonId,
                'floor_ids' => $floorIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * オーファンフロアの検出
     */
    public function detectOrphanFloors(): array
    {
        // タイプ1: dungeon_id が null
        $nullParentFloors = Route::where('category', 'dungeon')
                                ->whereNull('dungeon_id')
                                ->orderBy('name')
                                ->get();

        // タイプ2: dungeon_id は設定されているが親が存在しない
        $existingParentIds = DungeonDesc::pluck('dungeon_id')->toArray();
        $missingParentFloors = Route::where('category', 'dungeon')
                                   ->whereNotNull('dungeon_id')
                                   ->whereNotIn('dungeon_id', $existingParentIds)
                                   ->orderBy('name')
                                   ->get();

        return [
            'orphan_floors' => $nullParentFloors,
            'missing_parent_floors' => $missingParentFloors,
            'total_issues' => $nullParentFloors->count() + $missingParentFloors->count()
        ];
    }

    /**
     * オーファンフロアを既存の親にアタッチ
     */
    public function attachOrphansToExistingParent(int $parentId, array $floorIds): array
    {
        try {
            return DB::transaction(function () use ($parentId, $floorIds) {
                $parent = DungeonDesc::find($parentId);
                if (!$parent) {
                    throw new \Exception('指定された親ダンジョンが見つかりません。');
                }

                // オーファンフロアの検証
                $floors = Route::whereIn('id', $floorIds)
                              ->where('category', 'dungeon')
                              ->get();

                // 一括更新実行
                $updatedCount = Route::whereIn('id', $floorIds)
                                   ->where('category', 'dungeon')
                                   ->update(['dungeon_id' => $parent->dungeon_id]);

                return [
                    'success' => true,
                    'updated_count' => $updatedCount,
                    'parent' => $parent,
                    'floors' => $floors
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to attach orphans to existing parent', [
                'parent_id' => $parentId,
                'floor_ids' => $floorIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 新しい親を作成してオーファンフロアをアタッチ
     */
    public function createParentAndAttachOrphans(array $parentData, array $floorIds): array
    {
        try {
            return DB::transaction(function () use ($parentData, $floorIds) {
                // 新しい親ダンジョンを作成
                $parent = DungeonDesc::create([
                    'dungeon_id' => $parentData['dungeon_id'],
                    'dungeon_name' => $parentData['dungeon_name'],
                    'dungeon_desc' => $parentData['dungeon_desc'] ?? null,
                    'is_active' => true
                ]);

                // オーファンフロアの検証
                $floors = Route::whereIn('id', $floorIds)
                              ->where('category', 'dungeon')
                              ->get();

                // フロアを新しい親にアタッチ
                $updatedCount = Route::whereIn('id', $floorIds)
                                   ->where('category', 'dungeon')
                                   ->update(['dungeon_id' => $parent->dungeon_id]);

                return [
                    'success' => true,
                    'updated_count' => $updatedCount,
                    'parent' => $parent,
                    'floors' => $floors
                ];
            });

        } catch (\Exception $e) {
            Log::error('Failed to create parent and attach orphans', [
                'parent_data' => $parentData,
                'floor_ids' => $floorIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ダンジョン統計情報の取得
     */
    public function getDungeonStatistics(bool $includeInactive = false, string $searchQuery = ''): array
    {
        $query = DungeonDesc::query();
        
        if (!$includeInactive) {
            $query->active();
        }
        
        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('dungeon_name', 'LIKE', '%' . $searchQuery . '%')
                  ->orWhere('dungeon_id', 'LIKE', '%' . $searchQuery . '%');
            });
        }
        
        $dungeons = $query->with('floors')->get();
        
        return [
            'total_dungeons' => $dungeons->count(),
            'active_dungeons' => $dungeons->where('is_active', true)->count(),
            'inactive_dungeons' => $dungeons->where('is_active', false)->count(),
            'total_floors' => $dungeons->sum(function($dungeon) { 
                return $dungeon->floors->count(); 
            }),
            'avg_floors_per_dungeon' => $dungeons->count() > 0 ? 
                $dungeons->avg(function($dungeon) { 
                    return $dungeon->floors->count(); 
                }) : 0,
            'dungeons_with_no_floors' => $dungeons->filter(function($dungeon) { 
                return $dungeon->floors->count() === 0; 
            })->count(),
            'max_floors_in_dungeon' => $dungeons->max(function($dungeon) { 
                return $dungeon->floors->count(); 
            }) ?: 0
        ];
    }

    /**
     * フロア整合性チェック
     */
    public function checkFloorIntegrity(): array
    {
        $issues = [];

        // オーファンフロアの検出
        $orphanData = $this->detectOrphanFloors();
        
        if ($orphanData['orphan_floors']->count() > 0) {
            $issues[] = [
                'type' => 'orphan_floors',
                'severity' => 'warning',
                'count' => $orphanData['orphan_floors']->count(),
                'message' => 'オーファンフロア（親に紐づいていないフロア）が存在します。',
                'floors' => $orphanData['orphan_floors']->pluck('id')->toArray()
            ];
        }

        if ($orphanData['missing_parent_floors']->count() > 0) {
            $issues[] = [
                'type' => 'missing_parent_floors',
                'severity' => 'error',
                'count' => $orphanData['missing_parent_floors']->count(),
                'message' => '存在しない親IDを参照しているフロアがあります。',
                'floors' => $orphanData['missing_parent_floors']->pluck('id')->toArray()
            ];
        }

        // フロアなしダンジョンの検出
        $emptyDungeons = DungeonDesc::doesntHave('floors')->get();
        if ($emptyDungeons->count() > 0) {
            $issues[] = [
                'type' => 'empty_dungeons',
                'severity' => 'info',
                'count' => $emptyDungeons->count(),
                'message' => 'フロアが設定されていないダンジョンがあります。',
                'dungeons' => $emptyDungeons->pluck('id')->toArray()
            ];
        }

        return [
            'total_issues' => count($issues),
            'issues' => $issues,
            'status' => count($issues) === 0 ? 'healthy' : 'has_issues'
        ];
    }
}