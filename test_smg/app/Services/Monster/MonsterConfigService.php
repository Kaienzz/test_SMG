<?php

namespace App\Services\Monster;

use Illuminate\Support\Facades\Log;
use App\Models\Monster;
use App\Models\MonsterSpawnList;
use App\Models\Route;
// LocationConfigService removed - using SQLite only

/**
 * モンスター設定管理サービス (SQLite対応・統合版)
 * 
 * SQLiteデータベースのmonsters, monster_spawn_lists, routesテーブルを管理
 * 新しい統合されたスポーンシステムを使用
 */
class MonsterConfigService
{
    public function __construct()
    {
        // SQLite only - no JSON dependencies
    }

    /**
     * モンスター設定を読み込み (SQLite版)
     * 
     * @return array
     */
    public function loadMonsters(): array
    {
        try {
            $monsters = Monster::all()->keyBy('id')->toArray();
            return $monsters;
        } catch (\Exception $e) {
            Log::error('Failed to load monsters from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 特定のモンスターを取得 (SQLite版)
     * 
     * @param string $monsterId
     * @return array|null
     */
    public function getMonster(string $monsterId): ?array
    {
        try {
            $monster = Monster::find($monsterId);
            return $monster ? $monster->toArray() : null;
        } catch (\Exception $e) {
            Log::error('Failed to get monster from database', [
                'monster_id' => $monsterId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 有効なモンスター一覧を取得 (SQLite版)
     * 
     * @return array
     */
    public function getActiveMonsters(): array
    {
        try {
            $monsters = Monster::where('is_active', true)->get()->keyBy('id')->toArray();
            return $monsters;
        } catch (\Exception $e) {
            Log::error('Failed to get active monsters from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Location別のスポーン設定を読み込み (統合版SQLite)
     * 
     * @return array
     */
    public function loadLocationSpawnConfigs(): array
    {
        try {
            $locations = Route::whereIn('category', ['road', 'dungeon'])
                                   ->with(['monsterSpawns.monster'])
                                   ->get()
                                   ->keyBy('id')
                                   ->map(function ($location) {
                                       $monsters = [];
                                       foreach ($location->monsterSpawns as $spawn) {
                                           if ($spawn->monster) {
                                               $monsters[$spawn->monster_id] = [
                                                   'monster_id' => $spawn->monster_id,
                                                   'spawn_rate' => (float) $spawn->spawn_rate,
                                                   'priority' => $spawn->priority,
                                                   'min_level' => $spawn->min_level,
                                                   'max_level' => $spawn->max_level,
                                                   'is_active' => $spawn->is_active,
                                               ];
                                           }
                                       }
                                       
                                       return [
                                           'id' => $location->id,
                                           'name' => $location->name,
                                           'description' => $location->description,
                                           'category' => $location->category,
                                           'spawn_description' => $location->spawn_description,
                                           'spawn_tags' => $location->spawn_tags ?? [],
                                           'monsters' => $monsters,
                                       ];
                                   })
                                   ->toArray();
                                   
            return $locations;
        } catch (\Exception $e) {
            Log::error('Failed to load location spawn configs from database', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 互換性のため: loadSpawnLists()のエイリアス
     * 
     * @return array
     * @deprecated Use loadLocationSpawnConfigs() instead
     */
    public function loadSpawnLists(): array
    {
        return $this->loadLocationSpawnConfigs();
    }

    /**
     * 指定されたpathwayのmonster spawn設定を取得 (統合版SQLite)
     * 
     * @param string $pathwayId
     * @return array
     */
    public function getMonsterSpawnsForPathway(string $pathwayId): array
    {
        try {
            // 新しい統合構造を使用してLocationから直接スポーン設定を取得
            $location = Route::with(['monsterSpawns.monster'])
                                   ->find($pathwayId);
                                   
            if (!$location) {
                Log::warning('Location not found in SQLite', [
                    'pathway_id' => $pathwayId
                ]);
                
                return [];
            }

            $monsters = [];
            foreach ($location->monsterSpawns as $spawn) {
                if ($spawn->monster && $spawn->is_active) {
                    $monsters[$spawn->monster_id] = [
                        'monster_id' => $spawn->monster_id,
                        'spawn_rate' => (float) $spawn->spawn_rate,
                        'priority' => $spawn->priority,
                        'min_level' => $spawn->min_level,
                        'max_level' => $spawn->max_level,
                        'is_active' => $spawn->is_active,
                    ];
                }
            }

            return $monsters;
            
        } catch (\Exception $e) {
            Log::error('Failed to get monster spawns for pathway from SQLite', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }


    /**
     * 指定されたpathwayのmonster spawn設定を保存 (統合版SQLite)
     * 
     * @param string $pathwayId
     * @param array $spawns
     * @return bool
     */
    public function saveMonsterSpawnsForPathway(string $pathwayId, array $spawns): bool
    {
        try {
            $location = Route::find($pathwayId);
            
            if (!$location) {
                Log::error('Location not found for monster spawns update', ['pathway_id' => $pathwayId]);
                return false;
            }

            // 新しい統合構造を使用してスポーン設定を保存
            \DB::beginTransaction();
            
            // 既存のスポーン設定を削除
            MonsterSpawnList::where('location_id', $pathwayId)->delete();
            
            // 新しいスポーン設定を追加
            foreach ($spawns as $monsterId => $spawnData) {
                MonsterSpawnList::create([
                    'location_id' => $pathwayId,
                    'monster_id' => $monsterId,
                    'spawn_rate' => $spawnData['spawn_rate'] ?? 0,
                    'priority' => $spawnData['priority'] ?? 0,
                    'min_level' => $spawnData['min_level'] ?? null,
                    'max_level' => $spawnData['max_level'] ?? null,
                    'is_active' => $spawnData['is_active'] ?? true,
                ]);
            }
            
            \DB::commit();
            
            Log::info('Monster spawns saved for pathway', [
                'pathway_id' => $pathwayId,
                'spawns_count' => count($spawns)
            ]);
            
            return true;

        } catch (\Exception $e) {
            \DB::rollback();
            Log::error('Failed to save monster spawns for pathway', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 指定されたpathwayでランダムにモンスターを選択 (統合版SQLite)
     * 
     * @param string $pathwayId
     * @param int|null $playerLevel プレイヤーレベル（レベル制限適用）
     * @return array|null
     */
    public function getRandomMonsterForPathway(string $pathwayId, ?int $playerLevel = null): ?array
    {
        try {
            // 新しい統合構造を使用してLocationから直接スポーン設定を取得
            $location = Route::with(['monsterSpawns.monster'])
                                   ->find($pathwayId);
            
            if (!$location) {
                Log::warning('No location found for pathway', ['pathway_id' => $pathwayId]);
                return null;
            }

            // アクティブなモンスタースポーンのみ取得、レベル制限も適用
            $activeSpawns = $location->monsterSpawns()
                                   ->where('is_active', true)
                                   ->whereHas('monster', function($query) {
                                       $query->where('is_active', true);
                                   })
                                   ->when($playerLevel, function($query) use ($playerLevel) {
                                       return $query->where(function($q) use ($playerLevel) {
                                           $q->where(function($levelQuery) use ($playerLevel) {
                                               // min_levelとmax_levelの両方がnullの場合、制限なし
                                               $levelQuery->whereNull('min_level')
                                                         ->whereNull('max_level');
                                           })
                                           ->orWhere(function($levelQuery) use ($playerLevel) {
                                               // min_levelのみ設定されている場合
                                               $levelQuery->whereNotNull('min_level')
                                                         ->whereNull('max_level')
                                                         ->where('min_level', '<=', $playerLevel);
                                           })
                                           ->orWhere(function($levelQuery) use ($playerLevel) {
                                               // max_levelのみ設定されている場合  
                                               $levelQuery->whereNull('min_level')
                                                         ->whereNotNull('max_level')
                                                         ->where('max_level', '>=', $playerLevel);
                                           })
                                           ->orWhere(function($levelQuery) use ($playerLevel) {
                                               // 両方設定されている場合
                                               $levelQuery->whereNotNull('min_level')
                                                         ->whereNotNull('max_level')
                                                         ->where('min_level', '<=', $playerLevel)
                                                         ->where('max_level', '>=', $playerLevel);
                                           });
                                       });
                                   })
                                   ->with('monster')
                                   ->get();

            if ($activeSpawns->isEmpty()) {
                Log::warning('No active monster spawns found for pathway', [
                    'pathway_id' => $pathwayId,
                    'player_level' => $playerLevel
                ]);
                return null;
            }

            // 出現率の合計を計算
            $totalRate = $activeSpawns->sum('spawn_rate');
            if ($totalRate <= 0) {
                Log::warning('Total spawn rate is zero for pathway', ['pathway_id' => $pathwayId]);
                return null;
            }

            // ランダム選択（重み付け）
            $random = mt_rand() / mt_getrandmax() * $totalRate;
            $cumulativeRate = 0;

            foreach ($activeSpawns as $spawn) {
                $cumulativeRate += $spawn->spawn_rate;
                if ($random <= $cumulativeRate && $spawn->monster) {
                    $monster = $spawn->monster->toArray();
                    
                    Log::debug('Monster selected for encounter via integrated system', [
                        'pathway_id' => $pathwayId,
                        'location_name' => $location->name,
                        'monster_id' => $monster['id'],
                        'monster_name' => $monster['name'],
                        'spawn_rate' => $spawn->spawn_rate,
                        'player_level' => $playerLevel,
                        'spawn_min_level' => $spawn->min_level,
                        'spawn_max_level' => $spawn->max_level
                    ]);
                    
                    return $monster;
                }
            }

            // フォールバック: 最初のモンスターを返す
            $firstSpawn = $activeSpawns->first();
            if ($firstSpawn && $firstSpawn->monster) {
                $monster = $firstSpawn->monster->toArray();
                
                Log::debug('Fallback monster selected via integrated system', [
                    'pathway_id' => $pathwayId,
                    'monster_id' => $monster['id'],
                    'monster_name' => $monster['name']
                ]);
                
                return $monster;
            }

            return null;
            
        } catch (\Exception $e) {
            Log::error('Failed to get random monster for pathway from integrated system', [
                'pathway_id' => $pathwayId,
                'player_level' => $playerLevel,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }


    /**
     * 指定されたpathwayの出現率合計を取得 (統合版SQLite)
     * 
     * @param string $pathwayId
     * @return float
     */
    public function getTotalSpawnRateForPathway(string $pathwayId): float
    {
        try {
            $location = Route::find($pathwayId);
            
            if (!$location) {
                return 0.0;
            }

            return (float) $location->monsterSpawns()
                                   ->where('is_active', true)
                                   ->sum('spawn_rate');
        } catch (\Exception $e) {
            Log::error('Failed to get total spawn rate for pathway', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            return 0.0;
        }
    }

    /**
     * pathwayのmonster spawn設定をバリデート (統合版SQLite)
     * 
     * @param string $pathwayId
     * @return array 検証結果
     */
    public function validatePathwaySpawns(string $pathwayId): array
    {
        try {
            $location = Route::with(['monsterSpawns.monster'])
                                   ->find($pathwayId);
            
            if (!$location) {
                return [
                    'valid' => false,
                    'total_rate' => 0,
                    'total_percentage' => 0,
                    'issues' => ['ロケーションが見つかりません'],
                    'spawn_count' => 0
                ];
            }

            $spawns = $location->monsterSpawns()->where('is_active', true)->with('monster')->get();
            $totalRate = $spawns->sum('spawn_rate');
            $issues = [];
            
            // 出現率の合計チェック
            if ($totalRate > 1.0) {
                $issues[] = "出現率の合計が100%を超えています (" . number_format($totalRate * 100, 1) . "%)";
            }
            
            if ($totalRate == 0) {
                $issues[] = "有効なモンスター出現設定がありません";
            }
            
            // モンスター存在チェック
            foreach ($spawns as $spawn) {
                if (!$spawn->monster) {
                    $issues[] = "モンスター '{$spawn->monster_id}' が見つかりません";
                } elseif (!$spawn->monster->is_active) {
                    $issues[] = "モンスター '{$spawn->monster->name}' ({$spawn->monster_id}) が無効化されています";
                }
            }
            
            // 重複チェック
            $monsterIds = $spawns->pluck('monster_id')->toArray();
            $duplicates = array_diff_assoc($monsterIds, array_unique($monsterIds));
            if (!empty($duplicates)) {
                $issues[] = "重複するモンスターが設定されています";
            }
            
            // レベル制限チェック
            foreach ($spawns as $spawn) {
                if ($spawn->min_level && $spawn->max_level && $spawn->min_level > $spawn->max_level) {
                    $issues[] = "モンスター '{$spawn->monster->name}' の最小レベル({$spawn->min_level})が最大レベル({$spawn->max_level})を上回っています";
                }
            }
            
            return [
                'valid' => empty($issues),
                'total_rate' => $totalRate,
                'total_percentage' => $totalRate * 100,
                'issues' => $issues,
                'spawn_count' => $spawns->count(),
                'all_spawns_count' => $location->monsterSpawns->count()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to validate pathway spawns', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'total_rate' => 0,
                'total_percentage' => 0,
                'issues' => ['検証中にエラーが発生しました'],
                'spawn_count' => 0
            ];
        }
    }

    /**
     * スポーン情報を統合したモンスターデータを取得（Admin画面用、統合版SQLite）
     * 
     * @return array
     */
    public function getActiveMonstersWithSpawnInfo(): array
    {
        try {
            $monsters = Monster::where('is_active', true)
                              ->with(['monsterSpawnLists.gameLocation'])
                              ->get();
            
            $monstersWithSpawn = [];
            
            foreach ($monsters as $monster) {
                $monsterData = $monster->toArray();
                $monsterData['spawn_roads'] = [];
                $monsterData['spawn_locations'] = [];
                $spawnRates = [];
                
                foreach ($monster->monsterSpawnLists as $spawn) {
                    if ($spawn->is_active && $spawn->gameLocation) {
                        $monsterData['spawn_locations'][] = $spawn->location_id;
                        $monsterData['spawn_roads'][] = $spawn->location_id; // 互換性のため
                        $spawnRates[] = (float) $spawn->spawn_rate;
                    }
                }
                
                // 重複を削除
                $monsterData['spawn_roads'] = array_unique($monsterData['spawn_roads']);
                $monsterData['spawn_locations'] = array_unique($monsterData['spawn_locations']);
                
                // spawn_rate統計を計算
                if (!empty($spawnRates)) {
                    $monsterData['spawn_rate'] = array_sum($spawnRates) / count($spawnRates); // 平均
                    $monsterData['max_spawn_rate'] = max($spawnRates); // 最大
                    $monsterData['min_spawn_rate'] = min($spawnRates); // 最小
                    $monsterData['spawn_rate_count'] = count($spawnRates); // 出現場所数
                } else {
                    $monsterData['spawn_rate'] = 0;
                    $monsterData['max_spawn_rate'] = 0;
                    $monsterData['min_spawn_rate'] = 0;
                    $monsterData['spawn_rate_count'] = 0;
                }
                
                $monstersWithSpawn[$monster->id] = $monsterData;
            }
            
            return $monstersWithSpawn;
            
        } catch (\Exception $e) {
            Log::error('Failed to get active monsters with spawn info from integrated system', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 利用可能な道路（パスウェイ）一覧を取得 (SQLite版)
     * 
     * @return array
     */
    public function getAvailablePathways(): array
    {
        try {
            $locations = Route::where('category', 'road')
                                    ->orWhere('category', 'dungeon')
                                    ->pluck('name', 'id')
                                    ->toArray();
            
            return $locations;
        } catch (\Exception $e) {
            Log::error('Failed to get available pathways from SQLite', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * キャッシュをクリア (SQLite版では不要だが互換性のため保持)
     */
    public function clearCache(): void
    {
        // SQLiteではEloquentが自動的にキャッシュを管理するため、特に処理は不要
        Log::debug('Cache clear requested for MonsterConfigService (SQLite version)');
    }
}