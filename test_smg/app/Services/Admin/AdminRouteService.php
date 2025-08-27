<?php

namespace App\Services\Admin;

use App\Models\Route;
use App\Models\RouteConnection;
use App\Models\MonsterSpawnList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Admin用ルート管理サービス（SQLite対応）
 * 
 * SQLiteデータベースのroutes, route_connectionsテーブルを管理
 */
class AdminRouteService
{
    /**
     * キャッシュ付き統計情報を取得
     */
    public function getCachedStatistics(): array
    {
        return Cache::remember('admin_route_statistics', now()->addMinutes(30), function () {
            return $this->getStatistics();
        });
    }

    /**
     * 統計情報キャッシュをクリア
     */
    public function clearStatisticsCache(): void
    {
        Cache::forget('admin_route_statistics');
    }

    /**
     * 統計情報を取得（実際のデータベースアクセス）
     */
    public function getStatistics(): array
    {
        try {
            $stats = [
                'roads_count' => Route::where('category', 'road')->count(),
                'towns_count' => Route::where('category', 'town')->count(),
                'dungeons_count' => Route::where('category', 'dungeon')->count(),
                'total_connections' => RouteConnection::count(),
                'locations_with_spawns' => Route::whereHas('monsterSpawns')->count(),
                'total_monster_spawns' => MonsterSpawnList::count(),
                'active_monster_spawns' => MonsterSpawnList::where('is_active', true)->count(),
            ];
            
            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get location statistics', ['error' => $e->getMessage()]);
            return [
                'roads_count' => 0,
                'towns_count' => 0,
                'dungeons_count' => 0,
                'total_connections' => 0,
                'locations_with_spawns' => 0,
                'total_monster_spawns' => 0,
                'active_monster_spawns' => 0,
            ];
        }
    }

    /**
     * ルート基本データをキャッシュから取得
     */
    public function getCachedRoutesBase(): array
    {
        return Cache::remember('admin_routes_base_data', now()->addMinutes(15), function () {
            return Route::whereIn('category', ['road', 'dungeon'])
                        ->with(['monsterSpawns.monster', 'sourceConnections.targetLocation'])
                        ->get()
                        ->map(function($location) {
                            $totalSpawnRate = $location->monsterSpawns->sum('spawn_rate');
                            $activeSpawnsCount = $location->monsterSpawns->where('is_active', true)->count();
                            
                            return [
                                'id' => $location->id,
                                'name' => $location->name,
                                'description' => $location->description,
                                'category' => $location->category,
                                'length' => $location->length,
                                'difficulty' => $location->difficulty,
                                'encounter_rate' => $location->encounter_rate,
                                'spawn_tags' => $location->spawn_tags ?? [],
                                'spawn_description' => $location->spawn_description,
                                'monster_spawns_count' => $location->monsterSpawns->count(),
                                'active_spawns_count' => $activeSpawnsCount,
                                'total_spawn_rate' => round($totalSpawnRate, 3),
                                'spawn_completion' => $totalSpawnRate >= 0.99 ? 'complete' : ($totalSpawnRate > 0 ? 'partial' : 'none'),
                                'connections_count' => $location->sourceConnections->count(),
                                'is_active' => $location->is_active,
                                'floors' => $location->floors,
                                'min_level' => $location->min_level,
                                'max_level' => $location->max_level,
                            ];
                        })->toArray();
        });
    }

    /**
     * ルートキャッシュをクリア
     */
    public function clearRoutesCache(): void
    {
        Cache::forget('admin_routes_base_data');
        Cache::forget('admin_towns_base_data');
    }

    /**
     * ルート（道路・ダンジョン）一覧を取得（フィルタリング対応）
     */
    public function getRoutes(array $filters = []): array
    {
        try {
            // フィルタが未指定の場合はキャッシュから直接返す
            if (empty($filters) || empty(array_filter($filters))) {
                return $this->getCachedRoutesBase();
            }

            // キャッシュデータを取得してフィルタリング適用
            $routes = collect($this->getCachedRoutesBase());

            // Collectionベースのフィルタリング
            if (!empty($filters['search'])) {
                $routes = $routes->filter(function($route) use ($filters) {
                    return stripos($route['name'], $filters['search']) !== false ||
                           stripos($route['id'], $filters['search']) !== false ||
                           stripos($route['description'], $filters['search']) !== false;
                });
            }

            if (!empty($filters['category'])) {
                $routes = $routes->where('category', $filters['category']);
            }

            if (!empty($filters['difficulty'])) {
                $routes = $routes->where('difficulty', $filters['difficulty']);
            }

            // スポーン設定でフィルタリング
            if (isset($filters['has_spawns'])) {
                if ($filters['has_spawns']) {
                    $routes = $routes->where('monster_spawns_count', '>', 0);
                } else {
                    $routes = $routes->where('monster_spawns_count', 0);
                }
            }

            // ソート
            $sortBy = $filters['sort_by'] ?? 'id';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            if ($sortDirection === 'desc') {
                $routes = $routes->sortByDesc($sortBy);
            } else {
                $routes = $routes->sortBy($sortBy);
            }

            return $routes->values()->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get pathways', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 町基本データをキャッシュから取得
     */
    public function getCachedTownsBase(): array
    {
        return Cache::remember('admin_towns_base_data', now()->addMinutes(15), function () {
            return Route::where('category', 'town')
                        ->with(['sourceConnections.targetLocation'])
                        ->get()
                        ->map(function($location) {
                            return [
                                'id' => $location->id,
                                'name' => $location->name,
                                'description' => $location->description,
                                'services' => $location->services ?? [],
                                'connections_count' => $location->sourceConnections->count(),
                                'connections' => $location->sourceConnections->map(function($conn) {
                                    return [
                                        'type' => $conn->connection_type,
                                        'direction' => $conn->direction,
                                        'target' => $conn->targetLocation?->name,
                                        'target_category' => $conn->targetLocation?->category,
                                    ];
                                })->toArray(),
                            ];
                        })->toArray();
        });
    }

    /**
     * 町一覧を取得（フィルタリング対応）
     */
    public function getTowns(array $filters = []): array
    {
        try {
            // フィルタが未指定の場合はキャッシュから直接返す
            if (empty($filters) || empty(array_filter($filters))) {
                return $this->getCachedTownsBase();
            }

            // キャッシュデータを取得してフィルタリング適用
            $towns = collect($this->getCachedTownsBase());

            // Collectionベースのフィルタリング
            if (!empty($filters['search'])) {
                $towns = $towns->filter(function($town) use ($filters) {
                    return stripos($town['name'], $filters['search']) !== false ||
                           stripos($town['id'], $filters['search']) !== false;
                });
            }

            // ソート
            $sortBy = $filters['sort_by'] ?? 'id';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            if ($sortDirection === 'desc') {
                $towns = $towns->sortByDesc($sortBy);
            } else {
                $towns = $towns->sortBy($sortBy);
            }

            return $towns->values()->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get towns', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 接続情報一覧を取得
     */
    public function getConnections(array $filters = []): array
    {
        try {
            $query = RouteConnection::with(['sourceLocation', 'targetLocation']);

            // フィルタリング
            if (!empty($filters['connection_type'])) {
                $query->where('connection_type', $filters['connection_type']);
            }

            if (!empty($filters['source_location'])) {
                $query->where('source_location_id', $filters['source_location']);
            }

            // ソート
            $sortBy = $filters['sort_by'] ?? 'source_location_id';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            $query->orderBy($sortBy, $sortDirection);

            $connections = $query->get();

            return $connections->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'source_location_id' => $connection->source_location_id,
                    'source_name' => $connection->sourceLocation?->name,
                    'source_category' => $connection->sourceLocation?->category,
                    'target_location_id' => $connection->target_location_id,
                    'target_name' => $connection->targetLocation?->name,
                    'target_category' => $connection->targetLocation?->category,
                    'connection_type' => $connection->connection_type,
                    'direction' => $connection->direction,
                    'position' => $connection->position,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get connections', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * モンスタースポーン概要を取得（統合版）
     */
    public function getMonsterSpawnSummary(): array
    {
        try {
            $locations = Route::whereIn('category', ['road', 'dungeon'])
                                   ->with(['monsterSpawns.monster'])
                                   ->get();

            return $locations->map(function($location) {
                $spawns = $location->monsterSpawns;
                $totalRate = $spawns->sum('spawn_rate');
                
                return [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'location_category' => $location->category,
                    'spawn_tags' => $location->spawn_tags ?? [],
                    'spawn_description' => $location->spawn_description,
                    'monsters_count' => $spawns->count(),
                    'active_monsters_count' => $spawns->where('is_active', true)->count(),
                    'total_spawn_rate' => round($totalRate, 3),
                    'is_complete' => $totalRate >= 0.99,
                    'monsters' => $spawns->map(function($spawn) {
                        return [
                            'monster_id' => $spawn->monster_id,
                            'monster_name' => $spawn->monster->name,
                            'monster_level' => $spawn->monster->level,
                            'spawn_rate' => $spawn->spawn_rate,
                            'priority' => $spawn->priority,
                            'is_active' => $spawn->is_active,
                        ];
                    })->toArray(),
                ];
            })->filter(function($location) {
                return $location['monsters_count'] > 0; // スポーン設定があるLocationのみ
            })->values()->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get monster spawn summary', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ルート詳細を取得（拡張版：モジュラー情報構造）
     */
    public function getRouteDetail(string $routeId, array $includeModules = []): ?array
    {
        try {
            $location = Route::with([
                'monsterSpawns.monster',
                'sourceConnections.targetLocation',
                'targetConnections.sourceLocation'
            ])->find($routeId);

            if (!$location) {
                return null;
            }

            $detail = $location->toArray();
            
            // デフォルトで含めるモジュール
            $defaultModules = ['basic_info', 'monster_spawns', 'connections'];
            $modules = empty($includeModules) ? $defaultModules : array_merge($defaultModules, $includeModules);
            
            // モジュール別データ構築
            $detail['modules'] = [];
            
            // 基本情報モジュール
            if (in_array('basic_info', $modules)) {
                $detail['modules']['basic_info'] = [
                    'title' => '基本情報',
                    'icon' => 'fas fa-info-circle',
                    'priority' => 1,
                    'data' => [
                        'id' => $location->id,
                        'name' => $location->name,
                        'category' => $location->category,
                        'description' => $location->description,
                        'length' => $location->length,
                        'difficulty' => $location->difficulty,
                        'encounter_rate' => $location->encounter_rate,
                        'floors' => $location->floors,
                        'min_level' => $location->min_level,
                        'max_level' => $location->max_level,
                        'is_active' => $location->is_active,
                    ]
                ];
            }

            // モンスタースポーンモジュール
            if (in_array('monster_spawns', $modules)) {
                $detail['modules']['monster_spawns'] = [
                    'title' => 'モンスタースポーン',
                    'icon' => 'fas fa-dragon',
                    'priority' => 2,
                    'data' => [
                        'total_spawns' => $location->monsterSpawns->count(),
                        'active_spawns' => $location->monsterSpawns->where('is_active', true)->count(),
                        'total_spawn_rate' => round($location->monsterSpawns->sum('spawn_rate'), 3),
                        'spawn_tags' => $location->spawn_tags ?? [],
                        'spawn_description' => $location->spawn_description,
                        'completion_rate' => round($location->monsterSpawns->sum('spawn_rate'), 3),
                        'is_complete' => $location->monsterSpawns->sum('spawn_rate') >= 0.99,
                        'unique_monsters' => $location->monsterSpawns->pluck('monster_id')->unique()->count(),
                        'average_level' => round($location->monsterSpawns->avg(function($spawn) {
                            return $spawn->monster?->level ?? 0;
                        }), 1),
                        'monsters' => $location->monsterSpawns->sortBy('priority')->map(function($spawn) {
                            return [
                                'spawn_id' => $spawn->id,
                                'monster_id' => $spawn->monster_id,
                                'monster_name' => $spawn->monster?->name,
                                'monster_level' => $spawn->monster?->level,
                                'monster_emoji' => $spawn->monster?->emoji,
                                'monster_hp' => $spawn->monster?->max_hp,
                                'monster_attack' => $spawn->monster?->attack,
                                'monster_defense' => $spawn->monster?->defense,
                                'monster_experience' => $spawn->monster?->experience_reward,
                                'spawn_rate' => $spawn->spawn_rate,
                                'priority' => $spawn->priority,
                                'min_level' => $spawn->min_level,
                                'max_level' => $spawn->max_level,
                                'is_active' => $spawn->is_active,
                            ];
                        })->values()->toArray(),
                    ]
                ];
            }

            // 接続情報モジュール
            if (in_array('connections', $modules)) {
                $detail['modules']['connections'] = [
                    'title' => '接続情報',
                    'icon' => 'fas fa-route',
                    'priority' => 3,
                    'data' => [
                        'outgoing_connections' => $location->sourceConnections->map(function($conn) {
                            return [
                                'id' => $conn->id,
                                'target_id' => $conn->target_location_id,
                                'target_name' => $conn->targetLocation?->name,
                                'target_category' => $conn->targetLocation?->category,
                                'connection_type' => $conn->connection_type,
                                'direction' => $conn->direction,
                                'position' => $conn->position,
                            ];
                        })->toArray(),
                        'incoming_connections' => $location->targetConnections->map(function($conn) {
                            return [
                                'id' => $conn->id,
                                'source_id' => $conn->source_location_id,
                                'source_name' => $conn->sourceLocation?->name,
                                'source_category' => $conn->sourceLocation?->category,
                                'connection_type' => $conn->connection_type,
                                'direction' => $conn->direction,
                                'position' => $conn->position,
                            ];
                        })->toArray(),
                    ]
                ];
            }

            // 採集情報モジュール（将来実装用のプレースホルダー）
            if (in_array('gathering', $modules)) {
                $detail['modules']['gathering'] = [
                    'title' => '採集設定',
                    'icon' => 'fas fa-leaf',
                    'priority' => 4,
                    'data' => [
                        'gathering_nodes' => [], // 将来実装
                        'total_nodes' => 0,
                        'active_nodes' => 0,
                        'available_resources' => [],
                        'gathering_difficulty' => null,
                        'tool_requirements' => [],
                    ]
                ];
            }

            // イベント情報モジュール（将来実装用のプレースホルダー）
            if (in_array('events', $modules)) {
                $detail['modules']['events'] = [
                    'title' => 'イベント・特殊行動',
                    'icon' => 'fas fa-star',
                    'priority' => 5,
                    'data' => [
                        'special_events' => [], // 将来実装
                        'random_events' => [],
                        'treasure_spots' => [],
                        'npc_encounters' => [],
                        'rest_spots' => [],
                    ]
                ];
            }

            // 施設情報モジュール（将来実装用のプレースホルダー）
            if (in_array('facilities', $modules)) {
                $detail['modules']['facilities'] = [
                    'title' => '施設・商人',
                    'icon' => 'fas fa-store',
                    'priority' => 6,
                    'data' => [
                        'facilities' => [], // 将来実装
                        'traveling_merchants' => [],
                        'facility_count' => 0,
                        'available_items' => [],
                    ]
                ];
            }

            // モジュールを優先度順にソート
            uasort($detail['modules'], function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });

            // 互換性のため、従来の構造も保持
            if (isset($detail['modules']['monster_spawns'])) {
                $detail['spawn_info'] = $detail['modules']['monster_spawns']['data'];
                $detail['spawn_stats'] = [
                    'completion_rate' => $detail['modules']['monster_spawns']['data']['completion_rate'],
                    'is_complete' => $detail['modules']['monster_spawns']['data']['is_complete'],
                    'unique_monsters' => $detail['modules']['monster_spawns']['data']['unique_monsters'],
                    'average_level' => $detail['modules']['monster_spawns']['data']['average_level'],
                ];
            }

            return $detail;

        } catch (\Exception $e) {
            Log::error('Failed to get route detail', [
                'route_id' => $routeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 利用可能な難易度一覧を取得
     */
    public function getAvailableDifficulties(): array
    {
        try {
            return Route::whereNotNull('difficulty')
                              ->distinct()
                              ->pluck('difficulty')
                              ->filter()
                              ->sort()
                              ->values()
                              ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get difficulties', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * バックアップ情報を取得（互換性のため）
     */
    public function getRecentBackups(): array
    {
        // SQLiteベースなので、データベースバックアップ情報を返す
        return [
            [
                'filename' => 'sqlite_database_backup.sql',
                'path' => database_path('database.sqlite'),
                'size' => file_exists(database_path('database.sqlite')) ? filesize(database_path('database.sqlite')) : 0,
                'modified' => file_exists(database_path('database.sqlite')) ? filemtime(database_path('database.sqlite')) : time(),
                'type' => 'SQLite Database'
            ]
        ];
    }

    /**
     * 設定ステータスを取得（互換性のため）
     */
    public function getConfigStatus(): array
    {
        return [
            'file_exists' => file_exists(database_path('database.sqlite')),
            'file_size' => file_exists(database_path('database.sqlite')) ? filesize(database_path('database.sqlite')) : 0,
            'last_modified' => date('Y-m-d H:i:s'),
            'version' => 'SQLite Database',
            'type' => 'SQLite'
        ];
    }
}