<?php

namespace App\Services\Monster;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\Location\LocationConfigService;

/**
 * モンスター設定管理サービス
 * 
 * monsters.jsonとlocations.jsonのmonster_spawns部分を管理
 */
class MonsterConfigService
{
    private string $monstersConfigPath;
    private string $spawnConfigsPath;
    private string $spawnListsPath;
    private LocationConfigService $locationConfigService;
    private ?array $monstersCache = null;
    private ?array $spawnConfigsCache = null;
    private ?array $spawnListsCache = null;

    public function __construct(LocationConfigService $locationConfigService = null)
    {
        $this->monstersConfigPath = config_path('monsters/monsters.json');
        $this->spawnConfigsPath = config_path('monsters/monster_spawn_configs.json');
        $this->spawnListsPath = config_path('monsters/monster_spawn_lists.json');
        $this->locationConfigService = $locationConfigService ?? new LocationConfigService();
    }

    /**
     * モンスター設定を読み込み
     * 
     * @return array
     */
    public function loadMonsters(): array
    {
        if ($this->monstersCache !== null) {
            return $this->monstersCache;
        }

        try {
            if (!File::exists($this->monstersConfigPath)) {
                Log::error('Monsters config file not found', ['path' => $this->monstersConfigPath]);
                return [];
            }

            $content = File::get($this->monstersConfigPath);
            $monsters = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in monsters config', [
                    'error' => json_last_error_msg(),
                    'path' => $this->monstersConfigPath
                ]);
                return [];
            }

            $this->monstersCache = $monsters ?? [];
            return $this->monstersCache;

        } catch (\Exception $e) {
            Log::error('Failed to load monsters config', [
                'error' => $e->getMessage(),
                'path' => $this->monstersConfigPath
            ]);
            return [];
        }
    }

    /**
     * モンスター設定を保存
     * 
     * @param array $monsters
     * @return bool
     */
    public function saveMonsters(array $monsters): bool
    {
        try {
            $content = json_encode($monsters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!File::put($this->monstersConfigPath, $content)) {
                throw new \Exception('Failed to write to file');
            }

            $this->monstersCache = $monsters;
            Log::info('Monsters config saved successfully', ['path' => $this->monstersConfigPath]);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to save monsters config', [
                'error' => $e->getMessage(),
                'path' => $this->monstersConfigPath
            ]);
            return false;
        }
    }

    /**
     * 特定のモンスターを取得
     * 
     * @param string $monsterId
     * @return array|null
     */
    public function getMonster(string $monsterId): ?array
    {
        $monsters = $this->loadMonsters();
        return $monsters[$monsterId] ?? null;
    }

    /**
     * 有効なモンスター一覧を取得
     * 
     * @return array
     */
    public function getActiveMonsters(): array
    {
        $monsters = $this->loadMonsters();
        return array_filter($monsters, function($monster) {
            return $monster['is_active'] ?? true;
        });
    }

    /**
     * spawn config設定を読み込み
     * 
     * @return array
     */
    public function loadSpawnConfigs(): array
    {
        if ($this->spawnConfigsCache !== null) {
            return $this->spawnConfigsCache;
        }

        try {
            if (!File::exists($this->spawnConfigsPath)) {
                Log::error('Spawn configs file not found', ['path' => $this->spawnConfigsPath]);
                return [];
            }

            $content = File::get($this->spawnConfigsPath);
            $configs = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in spawn configs', [
                    'error' => json_last_error_msg(),
                    'path' => $this->spawnConfigsPath
                ]);
                return [];
            }

            $this->spawnConfigsCache = $configs['spawn_configs'] ?? [];
            return $this->spawnConfigsCache;

        } catch (\Exception $e) {
            Log::error('Failed to load spawn configs', [
                'error' => $e->getMessage(),
                'path' => $this->spawnConfigsPath
            ]);
            return [];
        }
    }

    /**
     * spawn config設定を保存
     * 
     * @param array $spawnConfigs
     * @return bool
     */
    public function saveSpawnConfigs(array $spawnConfigs): bool
    {
        try {
            $existingData = [];
            if (File::exists($this->spawnConfigsPath)) {
                $content = File::get($this->spawnConfigsPath);
                $existingData = json_decode($content, true) ?? [];
            }

            $data = array_merge($existingData, [
                'spawn_configs' => $spawnConfigs,
                'last_updated' => now()->toISOString(),
                'metadata' => array_merge($existingData['metadata'] ?? [], [
                    'total_configs' => count($spawnConfigs)
                ])
            ]);

            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!File::put($this->spawnConfigsPath, $content)) {
                throw new \Exception('Failed to write to file');
            }

            $this->spawnConfigsCache = $spawnConfigs;
            Log::info('Spawn configs saved successfully', ['path' => $this->spawnConfigsPath]);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to save spawn configs', [
                'error' => $e->getMessage(),
                'path' => $this->spawnConfigsPath
            ]);
            return false;
        }
    }

    /**
     * モンスター出現リスト設定を読み込み
     * 
     * @return array
     */
    public function loadSpawnLists(): array
    {
        if ($this->spawnListsCache !== null) {
            return $this->spawnListsCache;
        }

        try {
            if (!File::exists($this->spawnListsPath)) {
                Log::error('Spawn lists file not found', ['path' => $this->spawnListsPath]);
                return [];
            }

            $content = File::get($this->spawnListsPath);
            $lists = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in spawn lists', [
                    'error' => json_last_error_msg(),
                    'path' => $this->spawnListsPath
                ]);
                return [];
            }

            $this->spawnListsCache = $lists['spawn_lists'] ?? [];
            return $this->spawnListsCache;

        } catch (\Exception $e) {
            Log::error('Failed to load spawn lists', [
                'error' => $e->getMessage(),
                'path' => $this->spawnListsPath
            ]);
            return [];
        }
    }

    /**
     * 指定されたpathwayのmonster spawn設定を取得（新構造対応）
     * 
     * @param string $pathwayId
     * @return array
     */
    public function getMonsterSpawnsForPathway(string $pathwayId): array
    {
        $locationConfig = $this->locationConfigService->loadUnifiedConfig();
        
        // 新構造: spawn_list_idを使用（グループ化されたモンスターリスト）
        if (isset($locationConfig['pathways'][$pathwayId]['spawn_list_id'])) {
            $spawnListId = $locationConfig['pathways'][$pathwayId]['spawn_list_id'];
            $spawnLists = $this->loadSpawnLists();
            
            if (isset($spawnLists[$spawnListId])) {
                return $spawnLists[$spawnListId]['monsters'] ?? [];
            }
            
            Log::warning('Spawn list not found', [
                'pathway_id' => $pathwayId,
                'spawn_list_id' => $spawnListId
            ]);
            return [];
        }
        
        // 互換性: spawn_config_idsを使用
        if (isset($locationConfig['pathways'][$pathwayId]['spawn_config_ids'])) {
            $spawnConfigIds = $locationConfig['pathways'][$pathwayId]['spawn_config_ids'];
            $spawnConfigs = $this->loadSpawnConfigs();
            
            $spawns = [];
            foreach ($spawnConfigIds as $configId) {
                if (isset($spawnConfigs[$configId])) {
                    $spawns[$configId] = $spawnConfigs[$configId];
                }
            }
            return $spawns;
        }
        
        // 後方互換性: 古いmonster_spawns構造
        if (isset($locationConfig['pathways'][$pathwayId]['monster_spawns'])) {
            return $locationConfig['pathways'][$pathwayId]['monster_spawns'];
        }

        return [];
    }

    /**
     * 指定されたpathwayのmonster spawn設定を保存
     * 
     * @param string $pathwayId
     * @param array $spawns
     * @return bool
     */
    public function saveMonsterSpawnsForPathway(string $pathwayId, array $spawns): bool
    {
        try {
            $locationConfig = $this->locationConfigService->loadUnifiedConfig();
            
            if (!isset($locationConfig['pathways'][$pathwayId])) {
                Log::error('Pathway not found for monster spawns update', ['pathway_id' => $pathwayId]);
                return false;
            }

            $locationConfig['pathways'][$pathwayId]['monster_spawns'] = $spawns;
            
            return $this->locationConfigService->saveConfig($locationConfig);

        } catch (\Exception $e) {
            Log::error('Failed to save monster spawns for pathway', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 指定されたpathwayでランダムにモンスターを選択（新構造対応）
     * 
     * @param string $pathwayId
     * @return array|null
     */
    public function getRandomMonsterForPathway(string $pathwayId): ?array
    {
        $spawns = $this->getMonsterSpawnsForPathway($pathwayId);
        $monsters = $this->loadMonsters();
        
        if (empty($spawns)) {
            Log::warning('No monster spawns found for pathway', ['pathway_id' => $pathwayId]);
            return null;
        }

        // 有効なspawnのみフィルタ
        $activeSpawns = array_filter($spawns, function($spawn) {
            return $spawn['is_active'] ?? true;
        });

        if (empty($activeSpawns)) {
            Log::warning('No active monster spawns found for pathway', ['pathway_id' => $pathwayId]);
            return null;
        }

        // 出現率の合計を計算
        $totalRate = array_sum(array_column($activeSpawns, 'spawn_rate'));
        if ($totalRate <= 0) {
            Log::warning('Total spawn rate is zero for pathway', ['pathway_id' => $pathwayId]);
            return null;
        }

        // ランダム選択
        $random = mt_rand() / mt_getrandmax() * $totalRate;
        $cumulativeRate = 0;

        foreach ($activeSpawns as $spawnId => $spawn) {
            $cumulativeRate += $spawn['spawn_rate'];
            if ($random <= $cumulativeRate) {
                $monsterId = $spawn['monster_id'];
                $monster = $monsters[$monsterId] ?? null;
                if ($monster) {
                    Log::debug('Monster selected for encounter via spawn config', [
                        'pathway_id' => $pathwayId,
                        'spawn_config_id' => $spawnId,
                        'monster_id' => $monsterId,
                        'monster_name' => $monster['name']
                    ]);
                    return $monster;
                }
            }
        }

        // フォールバック: 最初のmonsterを返す
        $firstSpawn = reset($activeSpawns);
        $monsterId = $firstSpawn['monster_id'];
        $monster = $monsters[$monsterId] ?? null;
        if ($monster) {
            Log::debug('Fallback monster selected via spawn config', [
                'pathway_id' => $pathwayId,
                'spawn_config_id' => array_key_first($activeSpawns),
                'monster_id' => $monsterId,
                'monster_name' => $monster['name']
            ]);
        }
        
        return $monster;
    }

    /**
     * 指定されたpathwayの出現率合計を取得
     * 
     * @param string $pathwayId
     * @return float
     */
    public function getTotalSpawnRateForPathway(string $pathwayId): float
    {
        $spawns = $this->getMonsterSpawnsForPathway($pathwayId);
        
        $activeSpawns = array_filter($spawns, function($spawn) {
            return $spawn['is_active'] ?? true;
        });

        return (float) array_sum(array_column($activeSpawns, 'spawn_rate'));
    }

    /**
     * pathwayのmonster spawn設定をバリデート
     * 
     * @param string $pathwayId
     * @return array 検証結果
     */
    public function validatePathwaySpawns(string $pathwayId): array
    {
        $spawns = $this->getMonsterSpawnsForPathway($pathwayId);
        $monsters = $this->loadMonsters();
        $totalRate = $this->getTotalSpawnRateForPathway($pathwayId);
        
        $issues = [];
        
        // 出現率の合計チェック
        if ($totalRate > 1.0) {
            $issues[] = "出現率の合計が100%を超えています (" . number_format($totalRate * 100, 1) . "%)";
        }
        
        if ($totalRate == 0) {
            $issues[] = "有効なモンスター出現設定がありません";
        }
        
        // モンスター存在チェック
        foreach ($spawns as $spawnId => $spawn) {
            if (!isset($monsters[$spawn['monster_id']])) {
                $issues[] = "モンスター '{$spawn['monster_id']}' が見つかりません";
            }
        }
        
        // 重複チェック
        $monsterIds = array_column($spawns, 'monster_id');
        $duplicates = array_diff_assoc($monsterIds, array_unique($monsterIds));
        if (!empty($duplicates)) {
            $issues[] = "重複するモンスターが設定されています";
        }
        
        return [
            'valid' => empty($issues),
            'total_rate' => $totalRate,
            'total_percentage' => $totalRate * 100,
            'issues' => $issues,
            'spawn_count' => count($spawns)
        ];
    }

    /**
     * 全pathwayのmonster spawn設定をバリデート
     * 
     * @return array
     */
    public function validateAllPathwaySpawns(): array
    {
        $locationConfig = $this->locationConfigService->loadUnifiedConfig();
        $results = [];
        
        foreach ($locationConfig['pathways'] ?? [] as $pathwayId => $pathway) {
            $results[$pathwayId] = $this->validatePathwaySpawns($pathwayId);
        }
        
        return $results;
    }

    /**
     * モンスター出現リスト設定を保存
     * 
     * @param array $spawnLists
     * @return bool
     */
    public function saveSpawnLists(array $spawnLists): bool
    {
        try {
            $existingData = [];
            if (File::exists($this->spawnListsPath)) {
                $content = File::get($this->spawnListsPath);
                $existingData = json_decode($content, true) ?? [];
            }

            $data = array_merge($existingData, [
                'spawn_lists' => $spawnLists,
                'last_updated' => now()->toISOString(),
                'metadata' => array_merge($existingData['metadata'] ?? [], [
                    'total_spawn_lists' => count($spawnLists),
                    'total_monster_entries' => array_sum(array_map(function($list) {
                        return count($list['monsters'] ?? []);
                    }, $spawnLists))
                ])
            ]);

            $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (!File::put($this->spawnListsPath, $content)) {
                throw new \Exception('Failed to write to file');
            }

            $this->spawnListsCache = $spawnLists;
            Log::info('Spawn lists saved successfully', ['path' => $this->spawnListsPath]);
            
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to save spawn lists', [
                'error' => $e->getMessage(),
                'path' => $this->spawnListsPath
            ]);
            return false;
        }
    }

    /**
     * キャッシュをクリア
     */
    public function clearCache(): void
    {
        $this->monstersCache = null;
        $this->spawnConfigsCache = null;
        $this->spawnListsCache = null;
    }
}