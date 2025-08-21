<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminLocationService;
use App\Domain\Location\LocationService;
use App\Services\Monster\MonsterConfigService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * ロケーション管理コントローラー（SQLite対応）
 * 
 * SQLiteデータベースのロケーション設定を管理画面で管理
 */
class AdminLocationController extends AdminController
{
    private AdminLocationService $adminLocationService;
    private LocationService $locationService;
    private MonsterConfigService $monsterConfigService;

    public function __construct(AdminAuditService $auditService, AdminLocationService $adminLocationService, MonsterConfigService $monsterConfigService)
    {
        parent::__construct($auditService);
        $this->adminLocationService = $adminLocationService;
        $this->locationService = new LocationService();
        $this->monsterConfigService = $monsterConfigService;
    }

    /**
     * ロケーション管理トップページ
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.index');

        try {
            $data = [
                'stats' => $this->adminLocationService->getStatistics(),
                'recent_backups' => $this->adminLocationService->getRecentBackups(),
                'config_status' => $this->adminLocationService->getConfigStatus()
            ];

            return view('admin.locations.index', $data);

        } catch (\Exception $e) {
            Log::error('Failed to load location configuration for admin view', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.index', [
                'error' => 'ロケーションデータの読み込みに失敗しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 道路・ダンジョン統合管理ページ（SQLite対応）
     */
    public function pathways(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.pathways');

        $filters = $request->only(['search', 'category', 'type', 'difficulty', 'sort_by', 'sort_direction']);
        
        try {
            $pathways = $this->adminLocationService->getPathways($filters);

            // ソート処理
            $sortBy = $filters['sort_by'] ?? 'name';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            uasort($pathways, function($a, $b) use ($sortBy, $sortDirection) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';
                
                $result = strcmp($valueA, $valueB);
                return $sortDirection === 'desc' ? -$result : $result;
            });

            return view('admin.locations.pathways.index', [
                'pathways' => $pathways,
                'filters' => $filters,
                'total_count' => count($config['pathways'] ?? []),
                'filtered_count' => count($pathways),
                'categories' => $this->getPathwayCategories($config),
                'dungeon_types' => $this->getDungeonTypes($config)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 道路管理ページ
     */
    public function roads(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.roads');

        $filters = $request->only(['search', 'sort_by', 'sort_direction']);
        
        try {
            $config = $this->configService->loadConfig();
            $roads = $config['roads'] ?? [];

            // フィルタリング
            if (!empty($filters['search'])) {
                $roads = array_filter($roads, function($road, $roadId) use ($filters) {
                    return stripos($road['name'], $filters['search']) !== false ||
                           stripos($road['description'] ?? '', $filters['search']) !== false ||
                           stripos($roadId, $filters['search']) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            // ソート
            $sortBy = $filters['sort_by'] ?? 'name';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            uasort($roads, function($a, $b) use ($sortBy, $sortDirection) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';
                
                $result = strcmp($valueA, $valueB);
                return $sortDirection === 'desc' ? -$result : $result;
            });

            return view('admin.locations.roads.index', [
                'roads' => $roads,
                'filters' => $filters,
                'total_count' => count($config['roads'] ?? []),
                'filtered_count' => count($roads)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 道路作成・編集ページ
     */
    public function roadForm(Request $request, string $roadId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($roadId ? 'locations.edit' : 'locations.create');

        try {
            $config = $this->configService->loadConfig();
            $road = null;
            
            if ($roadId) {
                $road = $config['roads'][$roadId] ?? null;
                if (!$road) {
                    return redirect()->route('admin.locations.roads')->with('error', '指定された道路が見つかりません');
                }
                $road['id'] = $roadId;
            }

            return view('admin.locations.roads.form', [
                'road' => $road,
                'roadId' => $roadId,
                'isEdit' => $roadId !== null,
                'allLocations' => $this->getAllLocationsForConnections($config)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 道路保存処理
     */
    public function saveRoad(Request $request, string $roadId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($roadId ? 'locations.edit' : 'locations.create');

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'length' => 'required|integer|min:1|max:100',
            'difficulty' => 'required|in:easy,normal,hard',
            'encounter_rate' => 'required|numeric|min:0|max:1',
            'connections.start.type' => 'nullable|string',
            'connections.start.id' => 'nullable|string',
            'connections.end.type' => 'nullable|string',
            'connections.end.id' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $config = $this->configService->loadConfig();
            
            // 新規作成時のID生成
            if (!$roadId) {
                $roadId = $this->generateRoadId($config);
            }

            $oldData = $config['roads'][$roadId] ?? null;

            // 道路データ構築
            $roadData = [
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'length' => (int)$request->input('length'),
                'difficulty' => $request->input('difficulty'),
                'encounter_rate' => (float)$request->input('encounter_rate'),
            ];

            // 接続情報追加
            if ($request->input('connections.start.type') && $request->input('connections.start.id')) {
                $roadData['connections']['start'] = [
                    'type' => $request->input('connections.start.type'),
                    'id' => $request->input('connections.start.id')
                ];
            }

            if ($request->input('connections.end.type') && $request->input('connections.end.id')) {
                $roadData['connections']['end'] = [
                    'type' => $request->input('connections.end.type'),
                    'id' => $request->input('connections.end.id')
                ];
            }

            // 分岐情報の処理（必要に応じて）
            if ($request->has('branches')) {
                $roadData['branches'] = $this->processBranches($request->input('branches'));
            }

            $config['roads'][$roadId] = $roadData;

            // 設定保存
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog(
                $oldData ? 'locations.road.updated' : 'locations.road.created',
                [
                    'road_id' => $roadId,
                    'road_name' => $roadData['name'],
                    'old_data' => $oldData,
                    'new_data' => $roadData
                ]
            );

            $message = $oldData ? '道路情報を更新しました' : '新しい道路を作成しました';
            return redirect()->route('admin.locations.roads')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to save road', [
                'road_id' => $roadId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', '保存に失敗しました: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * 道路詳細情報を取得
     */
    public function roadDetails(Request $request, string $roadId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $config = $this->configService->loadConfig();
            $road = $config['roads'][$roadId] ?? null;

            if (!$road) {
                return response()->json(['error' => '道路が見つかりません'], 404);
            }

            $road['id'] = $roadId;

            return view('admin.locations.roads.details', [
                'road' => $road,
                'roadId' => $roadId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => '詳細情報の取得に失敗しました'], 500);
        }
    }

    /**
     * 町管理ページ
     */
    public function towns(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.towns');

        $filters = $request->only(['search', 'type', 'sort_by', 'sort_direction']);
        
        try {
            $config = $this->configService->loadConfig();
            $towns = $config['towns'] ?? [];

            // フィルタリング
            if (!empty($filters['search'])) {
                $towns = array_filter($towns, function($town, $townId) use ($filters) {
                    return stripos($town['name'], $filters['search']) !== false ||
                           stripos($town['description'] ?? '', $filters['search']) !== false ||
                           stripos($townId, $filters['search']) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            if (!empty($filters['type'])) {
                $towns = array_filter($towns, function($town) use ($filters) {
                    return ($town['type'] ?? '') === $filters['type'];
                });
            }

            return view('admin.locations.towns.index', [
                'towns' => $towns,
                'filters' => $filters,
                'town_types' => $this->getTownTypes($config),
                'total_count' => count($config['towns'] ?? []),
                'filtered_count' => count($towns)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン管理ページ
     */
    public function dungeons(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.dungeons');

        $filters = $request->only(['search', 'type', 'difficulty', 'sort_by', 'sort_direction']);
        
        try {
            $config = $this->configService->loadConfig();
            $dungeons = $config['dungeons'] ?? [];

            // フィルタリング処理
            if (!empty($filters['search'])) {
                $dungeons = array_filter($dungeons, function($dungeon, $dungeonId) use ($filters) {
                    return stripos($dungeon['name'], $filters['search']) !== false ||
                           stripos($dungeon['description'] ?? '', $filters['search']) !== false ||
                           stripos($dungeonId, $filters['search']) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            if (!empty($filters['type'])) {
                $dungeons = array_filter($dungeons, function($dungeon) use ($filters) {
                    return ($dungeon['type'] ?? '') === $filters['type'];
                });
            }

            if (!empty($filters['difficulty'])) {
                $dungeons = array_filter($dungeons, function($dungeon) use ($filters) {
                    return ($dungeon['difficulty'] ?? '') === $filters['difficulty'];
                });
            }

            // ソート処理
            $sortBy = $filters['sort_by'] ?? 'name';
            $sortDirection = $filters['sort_direction'] ?? 'asc';
            
            uasort($dungeons, function($a, $b) use ($sortBy, $sortDirection) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';
                
                $result = strcmp($valueA, $valueB);
                return $sortDirection === 'desc' ? -$result : $result;
            });

            return view('admin.locations.dungeons.index', [
                'dungeons' => $dungeons,
                'filters' => $filters,
                'total_count' => count($config['dungeons'] ?? []),
                'filtered_count' => count($dungeons)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 接続関係管理ページ
     */
    public function connections(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.connections');

        try {
            $config = $this->configService->loadConfig();
            
            return view('admin.locations.connections.index', [
                'connections' => $this->buildConnectionsMap($config),
                'roads' => $config['roads'] ?? [],
                'towns' => $config['towns'] ?? [],
                'dungeons' => $config['dungeons'] ?? []
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 設定エクスポート
     */
    public function exportConfig(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.export');

        try {
            $config = $this->configService->exportConfig();
            
            $this->auditLog('locations.config.exported', [
                'export_size' => strlen(json_encode($config)),
                'total_items' => count($config['roads'] ?? []) + count($config['towns'] ?? []) + count($config['dungeons'] ?? [])
            ]);

            $fileName = 'locations_config_' . date('Y-m-d_H-i-s') . '.json';
            $jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return response($jsonContent, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'エクスポートに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 設定インポート
     */
    public function importConfig(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.import');

        $validator = Validator::make($request->all(), [
            'config_file' => 'required|file|mimes:json|max:2048'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $file = $request->file('config_file');
            $content = file_get_contents($file->getRealPath());
            $config = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('無効なJSONファイルです: ' . json_last_error_msg());
            }

            // 設定インポート
            $this->configService->importConfig($config, true);

            $this->auditLog('locations.config.imported', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'total_items' => count($config['roads'] ?? []) + count($config['towns'] ?? []) + count($config['dungeons'] ?? [])
            ]);

            return redirect()->route('admin.locations.index')->with('success', '設定をインポートしました');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'インポートに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * バックアップから復元
     */
    public function restoreBackup(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validator = Validator::make($request->all(), [
            'backup_file' => 'required|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            $backupPath = $this->configService->getBackupPath() . '/' . $request->input('backup_file');
            $this->configService->restoreFromBackup($backupPath);

            $this->auditLog('locations.config.restored', [
                'backup_file' => $request->input('backup_file')
            ]);

            return redirect()->route('admin.locations.index')->with('success', 'バックアップから復元しました');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '復元に失敗しました: ' . $e->getMessage());
        }
    }

    // ===== プライベートヘルパーメソッド =====

    /**
     * 総接続数を計算
     */
    private function calculateTotalConnections(array $config): int
    {
        $connections = 0;
        
        // 道路の接続数
        foreach ($config['roads'] ?? [] as $road) {
            if (isset($road['connections']['start'])) $connections++;
            if (isset($road['connections']['end'])) $connections++;
            if (isset($road['branches'])) {
                foreach ($road['branches'] as $branchData) {
                    $connections += count($branchData);
                }
            }
        }
        
        // 町の接続数
        foreach ($config['towns'] ?? [] as $town) {
            $connections += count($town['connections'] ?? []);
        }
        
        return $connections;
    }

    /**
     * 最近のバックアップ一覧を取得
     */
    private function getRecentBackups(): array
    {
        try {
            $backups = $this->configService->getBackupList();
            return array_slice($backups, 0, 5); // 最新5件
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 設定ファイルサイズを取得
     */
    private function getConfigFileSize(): int
    {
        try {
            $path = $this->configService->getConfigPath();
            return file_exists($path) ? filesize($path) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 新しい道路IDを生成
     */
    private function generateRoadId(array $config): string
    {
        $existingIds = array_keys($config['roads'] ?? []);
        $maxNumber = 0;
        
        foreach ($existingIds as $id) {
            if (preg_match('/road_(\d+)/', $id, $matches)) {
                $maxNumber = max($maxNumber, (int)$matches[1]);
            }
        }
        
        return 'road_' . ($maxNumber + 1);
    }

    /**
     * 接続用の全ロケーション一覧を取得
     */
    private function getAllLocationsForConnections(array $config): array
    {
        $locations = [];
        
        foreach ($config['roads'] ?? [] as $roadId => $road) {
            $locations[] = [
                'type' => 'road',
                'id' => $roadId,
                'name' => $road['name'],
                'category' => '道路'
            ];
        }
        
        foreach ($config['towns'] ?? [] as $townId => $town) {
            $locations[] = [
                'type' => 'town',
                'id' => $townId,
                'name' => $town['name'],
                'category' => '町'
            ];
        }
        
        foreach ($config['dungeons'] ?? [] as $dungeonId => $dungeon) {
            $locations[] = [
                'type' => 'dungeon',
                'id' => $dungeonId,
                'name' => $dungeon['name'],
                'category' => 'ダンジョン'
            ];
        }
        
        return $locations;
    }

    /**
     * 分岐情報を処理
     */
    private function processBranches(array $branches): array
    {
        $processed = [];
        
        foreach ($branches as $position => $branchData) {
            if (is_array($branchData)) {
                $processed[(int)$position] = $branchData;
            }
        }
        
        return $processed;
    }

    /**
     * 町タイプ一覧を取得
     */
    private function getTownTypes(array $config): array
    {
        $types = [];
        foreach ($config['towns'] ?? [] as $town) {
            if (isset($town['type'])) {
                $types[$town['type']] = $town['type'];
            }
        }
        return array_unique($types);
    }

    /**
     * 接続マップを構築
     */
    private function buildConnectionsMap(array $config): array
    {
        $connections = [];
        
        // 道路の接続情報
        foreach ($config['roads'] ?? [] as $roadId => $road) {
            $processedConnections = [];
            
            // 通常の接続情報を処理
            if (isset($road['connections'])) {
                foreach ($road['connections'] as $direction => $connection) {
                    if ($connection && isset($connection['type']) && isset($connection['id'])) {
                        $processedConnections[$direction] = $connection;
                        $processedConnections[$direction]['name'] = $this->resolveLocationName($connection['type'], $connection['id'], $config);
                    }
                }
            }
            
            // 分岐情報を処理
            if (isset($road['branches'])) {
                foreach ($road['branches'] as $position => $branches) {
                    foreach ($branches as $branchDirection => $connection) {
                        if ($connection && isset($connection['type']) && isset($connection['id'])) {
                            $key = "分岐{$position}_{$branchDirection}";
                            $processedConnections[$key] = $connection;
                            $processedConnections[$key]['name'] = $this->resolveLocationName($connection['type'], $connection['id'], $config);
                        }
                    }
                }
            }
            
            // 接続情報がある場合のみ追加
            if (!empty($processedConnections)) {
                $connections[$roadId] = [
                    'type' => 'road',
                    'name' => $road['name'],
                    'connections' => $processedConnections
                ];
            }
        }
        
        // 町の接続情報
        foreach ($config['towns'] ?? [] as $townId => $town) {
            if (isset($town['connections'])) {
                $processedConnections = [];
                
                // 各接続先の名前を解決
                foreach ($town['connections'] as $direction => $connection) {
                    if ($connection && isset($connection['type']) && isset($connection['id'])) {
                        $processedConnections[$direction] = $connection;
                        $processedConnections[$direction]['name'] = $this->resolveLocationName($connection['type'], $connection['id'], $config);
                    }
                }
                
                if (!empty($processedConnections)) {
                    $connections[$townId] = [
                        'type' => 'town',
                        'name' => $town['name'],
                        'connections' => $processedConnections
                    ];
                }
            }
        }
        
        // ダンジョンの接続情報
        foreach ($config['dungeons'] ?? [] as $dungeonId => $dungeon) {
            if (isset($dungeon['connections'])) {
                $processedConnections = [];
                
                // 各接続先の名前を解決
                foreach ($dungeon['connections'] as $direction => $connection) {
                    if ($connection && isset($connection['type']) && isset($connection['id'])) {
                        $processedConnections[$direction] = $connection;
                        $processedConnections[$direction]['name'] = $this->resolveLocationName($connection['type'], $connection['id'], $config);
                    }
                }
                
                if (!empty($processedConnections)) {
                    $connections[$dungeonId] = [
                        'type' => 'dungeon',
                        'name' => $dungeon['name'],
                        'connections' => $processedConnections
                    ];
                }
            }
        }
        
        return $connections;
    }

    /**
     * ロケーション名を解決
     */
    private function resolveLocationName(string $type, string $id, array $config): string
    {
        switch ($type) {
            case 'road':
                return $config['roads'][$id]['name'] ?? $id;
            case 'town':
                return $config['towns'][$id]['name'] ?? $id;
            case 'dungeon':
                return $config['dungeons'][$id]['name'] ?? $id;
            default:
                return $id;
        }
    }

    /**
     * 町作成・編集ページ
     */
    public function townForm(Request $request, string $townId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($townId ? 'locations.edit' : 'locations.create');

        try {
            $config = $this->configService->loadConfig();
            $town = $townId ? ($config['towns'][$townId] ?? null) : null;
            $isEdit = $townId !== null;

            if ($isEdit && !$town) {
                return redirect()->route('admin.locations.towns')->with('error', '指定された町が見つかりません');
            }

            // 接続可能なロケーション一覧を取得
            $availableLocations = $this->getAllLocationsForConnections($config);

            return view('admin.locations.towns.form', [
                'town' => $town,
                'townId' => $townId,
                'isEdit' => $isEdit,
                'availableLocations' => $availableLocations
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'フォームの表示に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 町保存処理
     */
    public function saveTown(Request $request, string $townId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($townId ? 'locations.edit' : 'locations.create');

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:capital,commercial,residential,industrial,port,frontier',
            'connections' => 'nullable|array',
            'connections.*.type' => 'required_with:connections|in:road,town,dungeon',
            'connections.*.id' => 'required_with:connections|string'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $config = $this->configService->loadConfig();
            $oldData = $townId ? ($config['towns'][$townId] ?? null) : null;

            // 新しい町IDの生成
            if (!$townId) {
                $townId = $this->generateTownId($config);
            }

            // 町データの準備
            $townData = [
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'type' => $request->input('type'),
                'connections' => []
            ];

            // 接続情報の処理
            $connections = $request->input('connections', []);
            foreach ($connections as $direction => $connection) {
                if (!empty($connection['type']) && !empty($connection['id'])) {
                    $townData['connections'][$direction] = [
                        'type' => $connection['type'],
                        'id' => $connection['id']
                    ];
                }
            }

            // 設定ファイルを更新
            $config['towns'][$townId] = $townData;
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog(
                $oldData ? 'locations.town.updated' : 'locations.town.created',
                [
                    'town_id' => $townId,
                    'name' => $townData['name'],
                    'old_data' => $oldData,
                    'new_data' => $townData
                ]
            );

            $message = $oldData ? '町情報を更新しました' : '新しい町を作成しました';
            return redirect()->route('admin.locations.towns')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', '保存に失敗しました: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * 町削除処理
     */
    public function deleteTown(Request $request, string $townId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $config = $this->configService->loadConfig();
            $town = $config['towns'][$townId] ?? null;

            if (!$town) {
                return redirect()->back()->with('error', '指定された町が見つかりません');
            }

            // 町を削除
            unset($config['towns'][$townId]);
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog('locations.town.deleted', [
                'town_id' => $townId,
                'town_data' => $town
            ]);

            return redirect()->route('admin.locations.towns')->with('success', '町を削除しました');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン作成・編集ページ
     */
    public function dungeonForm(Request $request, string $dungeonId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($dungeonId ? 'locations.edit' : 'locations.create');

        try {
            $config = $this->configService->loadConfig();
            $dungeon = $dungeonId ? ($config['dungeons'][$dungeonId] ?? null) : null;
            $isEdit = $dungeonId !== null;

            if ($isEdit && !$dungeon) {
                return redirect()->route('admin.locations.dungeons')->with('error', '指定されたダンジョンが見つかりません');
            }

            return view('admin.locations.dungeons.form', [
                'dungeon' => $dungeon,
                'dungeonId' => $dungeonId,
                'isEdit' => $isEdit
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'フォームの表示に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン保存処理
     */
    public function saveDungeon(Request $request, string $dungeonId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($dungeonId ? 'locations.edit' : 'locations.create');

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:cave,ruins,tower,underground',
            'difficulty' => 'required|in:easy,normal,hard,extreme',
            'floors' => 'nullable|integer|min:1|max:100',
            'min_level' => 'nullable|integer|min:1',
            'max_level' => 'nullable|integer|min:1',
            'boss' => 'nullable|string|max:255'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            $config = $this->configService->loadConfig();
            $oldData = $dungeonId ? ($config['dungeons'][$dungeonId] ?? null) : null;

            // 新しいダンジョンIDの生成
            if (!$dungeonId) {
                $dungeonId = $this->generateDungeonId($config);
            }

            // ダンジョンデータの準備
            $dungeonData = [
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'type' => $request->input('type'),
                'difficulty' => $request->input('difficulty'),
                'floors' => $request->input('floors'),
                'min_level' => $request->input('min_level'),
                'max_level' => $request->input('max_level'),
                'boss' => $request->input('boss')
            ];

            // 設定ファイルを更新
            $config['dungeons'][$dungeonId] = $dungeonData;
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog(
                $oldData ? 'locations.dungeon.updated' : 'locations.dungeon.created',
                [
                    'dungeon_id' => $dungeonId,
                    'name' => $dungeonData['name'],
                    'old_data' => $oldData,
                    'new_data' => $dungeonData
                ]
            );

            $message = $oldData ? 'ダンジョン情報を更新しました' : '新しいダンジョンを作成しました';
            return redirect()->route('admin.locations.dungeons')->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                           ->with('error', '保存に失敗しました: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * ダンジョン詳細情報を取得
     */
    public function dungeonDetails(Request $request, string $dungeonId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $config = $this->configService->loadConfig();
            $dungeon = $config['dungeons'][$dungeonId] ?? null;

            if (!$dungeon) {
                return response()->json(['error' => 'ダンジョンが見つかりません'], 404);
            }

            $dungeon['id'] = $dungeonId;

            return view('admin.locations.dungeons.details', [
                'dungeon' => $dungeon,
                'dungeonId' => $dungeonId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => '詳細情報の取得に失敗しました'], 500);
        }
    }

    /**
     * ダンジョン削除処理
     */
    public function deleteDungeon(Request $request, string $dungeonId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $config = $this->configService->loadConfig();
            $dungeon = $config['dungeons'][$dungeonId] ?? null;

            if (!$dungeon) {
                return redirect()->back()->with('error', '指定されたダンジョンが見つかりません');
            }

            // ダンジョンを削除
            unset($config['dungeons'][$dungeonId]);
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog('locations.dungeon.deleted', [
                'dungeon_id' => $dungeonId,
                'dungeon_data' => $dungeon
            ]);

            return redirect()->route('admin.locations.dungeons')->with('success', 'ダンジョンを削除しました');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 新しい町IDを生成
     */
    private function generateTownId(array $config): string
    {
        $existingIds = array_keys($config['towns'] ?? []);
        $maxNumber = 0;
        
        foreach ($existingIds as $id) {
            if (preg_match('/town_(\d+)/', $id, $matches)) {
                $maxNumber = max($maxNumber, (int)$matches[1]);
            }
        }
        
        return 'town_' . ($maxNumber + 1);
    }

    /**
     * 新しいダンジョンIDを生成
     */
    private function generateDungeonId(array $config): string
    {
        $existingIds = array_keys($config['dungeons'] ?? []);
        $maxNumber = 0;
        
        foreach ($existingIds as $id) {
            if (preg_match('/dungeon_(\d+)/', $id, $matches)) {
                $maxNumber = max($maxNumber, (int)$matches[1]);
            }
        }
        
        return 'dungeon_' . ($maxNumber + 1);
    }

    // ===== 統合管理用メソッド =====

    /**
     * pathway作成・編集ページ
     */
    public function pathwayForm(Request $request, string $pathwayId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($pathwayId ? 'locations.edit' : 'locations.create');

        try {
            $config = $this->configService->loadUnifiedConfig();
            $pathway = null;
            
            if ($pathwayId) {
                $pathway = $config['pathways'][$pathwayId] ?? null;
                if (!$pathway) {
                    return redirect()->route('admin.locations.pathways')->with('error', '指定されたpathwayが見つかりません');
                }
                $pathway['id'] = $pathwayId;
            }

            // モンスタースポーンリストを取得
            $spawnLists = $this->monsterConfigService->loadSpawnLists();
            $availableSpawnLists = [];
            foreach ($spawnLists as $listId => $listData) {
                $availableSpawnLists[$listId] = $listData['name'] ?? $listId;
            }

            return view('admin.locations.pathways.form', [
                'pathway' => $pathway,
                'pathwayId' => $pathwayId,
                'isEdit' => $pathwayId !== null,
                'allLocations' => $this->getAllLocationsForConnections($config),
                'availableSpawnLists' => $availableSpawnLists,
                'categories' => ['road' => '道路', 'dungeon' => 'ダンジョン'],
                'dungeonTypes' => [
                    'cave' => '洞窟',
                    'ruins' => '遺跡',
                    'tower' => '塔',
                    'underground' => '地下'
                ],
                'difficulties' => [
                    'easy' => '簡単',
                    'normal' => '普通',
                    'hard' => '困難',
                    'extreme' => '極難'
                ]
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * pathway保存処理
     */
    public function savePathway(Request $request, string $pathwayId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission($pathwayId ? 'locations.edit' : 'locations.create');

        $rules = [
            'pathway_id' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_-]+$/', // 半角英数字・アンダースコア・ハイフンのみ
                function($attribute, $value, $fail) use ($pathwayId) {
                    // ID変更時の重複チェック
                    if ($pathwayId && $pathwayId !== $value) {
                        $config = $this->configService->loadUnifiedConfig();
                        if (isset($config['pathways'][$value])) {
                            $fail('このIDは既に使用されています。');
                        }
                    }
                }
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:road,dungeon',
            'length' => 'required|integer|min:1|max:1000',
            'difficulty' => 'required|in:easy,normal,hard,extreme',
            'encounter_rate' => 'required|numeric|min:0|max:1',
            'spawn_list_id' => 'nullable|string|max:255',
            'connections.start.type' => 'nullable|string',
            'connections.start.id' => 'nullable|string',
            'connections.end.type' => 'nullable|string',
            'connections.end.id' => 'nullable|string',
        ];

        // ダンジョン固有フィールドのバリデーション追加
        if ($request->input('category') === 'dungeon') {
            $rules = array_merge($rules, [
                'dungeon_type' => 'required|in:cave,ruins,tower,underground',
                'floors' => 'nullable|integer|min:1|max:100',
                'min_level' => 'nullable|integer|min:1',
                'max_level' => 'nullable|integer|min:1',
                'boss' => 'nullable|string|max:255'
            ]);
        }

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $config = $this->configService->loadUnifiedConfig();
            
            // 新規作成時のID生成 vs ID指定の処理
            $newPathwayId = $request->input('pathway_id');
            
            if (!$pathwayId) {
                // 新規作成時
                if (isset($config['pathways'][$newPathwayId])) {
                    return redirect()->back()
                        ->withErrors(['pathway_id' => 'このIDは既に使用されています。'])
                        ->withInput();
                }
                $pathwayId = $newPathwayId;
            } else {
                // 編集時のID変更処理
                if ($pathwayId !== $newPathwayId) {
                    // ID変更時の整合性チェック
                    $idChangeResult = $this->validateAndProcessIdChange($pathwayId, $newPathwayId, $config);
                    if ($idChangeResult !== true) {
                        return redirect()->back()
                            ->with('error', $idChangeResult)
                            ->withInput();
                    }
                }
            }

            $oldData = $config['pathways'][$pathwayId] ?? null;

            // pathway データ構築
            $pathwayData = [
                'category' => $request->input('category'),
                'name' => $request->input('name'),
                'description' => $request->input('description', ''),
                'length' => (int)$request->input('length'),
                'difficulty' => $request->input('difficulty'),
                'encounter_rate' => (float)$request->input('encounter_rate'),
                'spawn_list_id' => $request->input('spawn_list_id', ''),
            ];

            // ダンジョン固有フィールド追加
            if ($request->input('category') === 'dungeon') {
                $pathwayData['dungeon_type'] = $request->input('dungeon_type');
                $pathwayData['floors'] = $request->input('floors') ? (int)$request->input('floors') : 1;
                $pathwayData['min_level'] = $request->input('min_level') ? (int)$request->input('min_level') : null;
                $pathwayData['max_level'] = $request->input('max_level') ? (int)$request->input('max_level') : null;
                $pathwayData['boss'] = $request->input('boss', '');
            }

            // 接続情報追加
            if ($request->input('connections.start.type') && $request->input('connections.start.id')) {
                $pathwayData['connections']['start'] = [
                    'type' => $request->input('connections.start.type'),
                    'id' => $request->input('connections.start.id')
                ];
            }

            if ($request->input('connections.end.type') && $request->input('connections.end.id')) {
                $pathwayData['connections']['end'] = [
                    'type' => $request->input('connections.end.type'),
                    'id' => $request->input('connections.end.id')
                ];
            }

            // 分岐情報の処理（必要に応じて）
            if ($request->has('branches')) {
                $pathwayData['branches'] = $this->processBranches($request->input('branches'));
            }

            // ID変更時の関連データ更新
            if ($pathwayId !== $newPathwayId) {
                // 関連データ更新
                $config = $this->updateRelatedDataForIdChange($config, $pathwayId, $newPathwayId);
                
                // 古いIDのデータを削除
                unset($config['pathways'][$pathwayId]);
                
                // 新しいIDで保存
                $config['pathways'][$newPathwayId] = $pathwayData;
                $pathwayId = $newPathwayId; // ログ用にIDを更新
            } else {
                $config['pathways'][$pathwayId] = $pathwayData;
            }

            // 設定保存
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog(
                $oldData ? 'locations.pathway.updated' : 'locations.pathway.created',
                [
                    'pathway_id' => $pathwayId,
                    'pathway_name' => $pathwayData['name'],
                    'category' => $pathwayData['category'],
                    'old_data' => $oldData,
                    'new_data' => $pathwayData
                ]
            );

            $message = $oldData ? 'pathway情報を更新しました' : '新しいpathwayを作成しました';
            return redirect()->route('admin.locations.pathways')->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to save pathway', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', '保存に失敗しました: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * pathway詳細情報を取得
     */
    public function pathwayDetails(Request $request, string $pathwayId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $config = $this->configService->loadUnifiedConfig();
            $pathway = $config['pathways'][$pathwayId] ?? null;

            if (!$pathway) {
                return response()->json(['error' => 'pathwayが見つかりません'], 404);
            }

            $pathway['id'] = $pathwayId;

            return view('admin.locations.pathways.details', [
                'pathway' => $pathway,
                'pathwayId' => $pathwayId
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => '詳細情報の取得に失敗しました'], 500);
        }
    }

    /**
     * pathway削除処理
     */
    public function deletePathway(Request $request, string $pathwayId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $config = $this->configService->loadUnifiedConfig();
            $pathway = $config['pathways'][$pathwayId] ?? null;

            if (!$pathway) {
                return redirect()->back()->with('error', '指定されたpathwayが見つかりません');
            }

            // pathwayを削除
            unset($config['pathways'][$pathwayId]);
            $this->configService->saveConfig($config);

            // 監査ログ記録
            $this->auditLog('locations.pathway.deleted', [
                'pathway_id' => $pathwayId,
                'pathway_data' => $pathway
            ]);

            return redirect()->route('admin.locations.pathways')->with('success', 'pathwayを削除しました');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * pathwayカテゴリー一覧を取得
     */
    private function getPathwayCategories(array $config): array
    {
        $categories = [];
        foreach ($config['pathways'] ?? [] as $pathway) {
            if (isset($pathway['category'])) {
                $categories[$pathway['category']] = $pathway['category'];
            }
        }
        return array_unique($categories);
    }

    /**
     * ダンジョンタイプ一覧を取得
     */
    private function getDungeonTypes(array $config): array
    {
        $types = [];
        foreach ($config['pathways'] ?? [] as $pathway) {
            if (isset($pathway['dungeon_type'])) {
                $types[$pathway['dungeon_type']] = $pathway['dungeon_type'];
            }
        }
        return array_unique($types);
    }

    /**
     * 新しいpathway IDを生成
     */
    private function generatePathwayId(array $config, string $category): string
    {
        $existingIds = array_keys($config['pathways'] ?? []);
        $prefix = $category === 'road' ? 'road_' : 'dungeon_';
        $maxNumber = 0;
        
        foreach ($existingIds as $id) {
            if (preg_match('/' . $prefix . '(\d+)/', $id, $matches)) {
                $maxNumber = max($maxNumber, (int)$matches[1]);
            }
        }
        
        return $prefix . ($maxNumber + 1);
    }

    // ===== データ移行機能 =====

    /**
     * データ移行状況を確認
     */
    public function migrationStatus(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $config = $this->configService->loadConfig(false);
            $isLegacyFormat = $this->isLegacyFormat($config);

            $migrationInfo = [
                'is_legacy_format' => $isLegacyFormat,
                'current_version' => $config['version'] ?? 'unknown',
                'target_version' => '2.0.0',
                'needs_migration' => $isLegacyFormat
            ];

            if ($isLegacyFormat) {
                $migrationInfo['legacy_data'] = [
                    'roads_count' => count($config['roads'] ?? []),
                    'dungeons_count' => count($config['dungeons'] ?? []),
                    'towns_count' => count($config['towns'] ?? [])
                ];
            } else {
                $migrationInfo['current_data'] = [
                    'pathways_count' => count($config['pathways'] ?? []),
                    'roads_count' => count(array_filter($config['pathways'] ?? [], fn($p) => ($p['category'] ?? '') === 'road')),
                    'dungeons_count' => count(array_filter($config['pathways'] ?? [], fn($p) => ($p['category'] ?? '') === 'dungeon')),
                    'towns_count' => count($config['towns'] ?? [])
                ];
            }

            return response()->json($migrationInfo);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * データ移行を実行
     */
    public function executeMigration(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $config = $this->configService->loadConfig(false);
            
            if (!$this->isLegacyFormat($config)) {
                return response()->json([
                    'success' => false,
                    'message' => '既に新しいフォーマットです。移行の必要はありません。'
                ]);
            }

            // 移行前バックアップを作成
            $backupPath = $this->configService->createBackup();

            // 移行実行
            $migratedConfig = $this->migrateLegacyFormat($config);
            $this->configService->saveConfig($migratedConfig, false);

            // 移行結果統計
            $statistics = [
                'migrated_roads' => count(array_filter($migratedConfig['pathways'] ?? [], fn($p) => ($p['category'] ?? '') === 'road')),
                'migrated_dungeons' => count(array_filter($migratedConfig['pathways'] ?? [], fn($p) => ($p['category'] ?? '') === 'dungeon')),
                'total_pathways' => count($migratedConfig['pathways'] ?? []),
                'towns_count' => count($migratedConfig['towns'] ?? []),
                'backup_file' => basename($backupPath)
            ];

            // 監査ログ記録
            $this->auditLog('locations.data.migrated', [
                'from_version' => $config['version'] ?? 'legacy',
                'to_version' => '2.0.0',
                'statistics' => $statistics,
                'backup_file' => $backupPath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'データ移行が完了しました',
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Data migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '移行中にエラーが発生しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 古いフォーマットかどうかを判定
     */
    private function isLegacyFormat(array $config): bool
    {
        return isset($config['roads']) && isset($config['dungeons']) && !isset($config['pathways']);
    }

    /**
     * 古いフォーマットから新しいフォーマットに移行
     */
    private function migrateLegacyFormat(array $config): array
    {
        Log::info('Manual data migration started');

        $newConfig = [
            'version' => '2.0.0',
            'last_updated' => now()->toISOString(),
            'description' => $config['description'] ?? 'Location configuration for test_smg game',
            'pathways' => [],
            'towns' => $config['towns'] ?? [],
            'metadata' => $config['metadata'] ?? []
        ];

        // 道路データの移行
        if (isset($config['roads']) && is_array($config['roads'])) {
            foreach ($config['roads'] as $roadId => $roadData) {
                $newConfig['pathways'][$roadId] = array_merge($roadData, [
                    'category' => 'road'
                ]);
            }
        }

        // ダンジョンデータの移行
        if (isset($config['dungeons']) && is_array($config['dungeons'])) {
            foreach ($config['dungeons'] as $dungeonId => $dungeonData) {
                $migratedDungeon = array_merge($dungeonData, [
                    'category' => 'dungeon'
                ]);

                // ダンジョン固有フィールドを推測・設定
                $migratedDungeon = $this->inferDungeonFields($migratedDungeon);

                $newConfig['pathways'][$dungeonId] = $migratedDungeon;
            }
        }

        // メタデータの更新
        if (isset($newConfig['metadata'])) {
            $pathwayCount = count($newConfig['pathways']);
            $roadCount = count(array_filter($newConfig['pathways'], fn($p) => $p['category'] === 'road'));
            $dungeonCount = count(array_filter($newConfig['pathways'], fn($p) => $p['category'] === 'dungeon'));

            $newConfig['metadata'] = array_merge($newConfig['metadata'], [
                'total_pathways' => $pathwayCount,
                'total_roads' => $roadCount,
                'total_dungeons' => $dungeonCount,
                'migration_date' => now()->toDateString(),
                'schema_version' => '2.0',
                'migration_type' => 'manual'
            ]);
        }

        return $newConfig;
    }

    /**
     * ダンジョンの固有フィールドを推測
     */
    private function inferDungeonFields(array $dungeonData): array
    {
        // ダンジョンタイプを名前から推測
        if (!isset($dungeonData['dungeon_type'])) {
            $name = strtolower($dungeonData['name'] ?? '');
            if (str_contains($name, '洞窟') || str_contains($name, 'cave')) {
                $dungeonData['dungeon_type'] = 'cave';
            } elseif (str_contains($name, '遺跡') || str_contains($name, 'ruins')) {
                $dungeonData['dungeon_type'] = 'ruins';
            } elseif (str_contains($name, '塔') || str_contains($name, 'tower')) {
                $dungeonData['dungeon_type'] = 'tower';
            } elseif (str_contains($name, '地下') || str_contains($name, 'underground')) {
                $dungeonData['dungeon_type'] = 'underground';
            } else {
                $dungeonData['dungeon_type'] = 'cave'; // デフォルト
            }
        }

        // フロア数を設定（未設定の場合）
        if (!isset($dungeonData['floors'])) {
            $dungeonData['floors'] = 1;
        }

        // レベル制限を難易度から推測
        if (!isset($dungeonData['min_level']) || !isset($dungeonData['max_level'])) {
            $difficulty = $dungeonData['difficulty'] ?? 'normal';
            switch ($difficulty) {
                case 'easy':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 1;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 5;
                    break;
                case 'normal':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 3;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 10;
                    break;
                case 'hard':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 8;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 20;
                    break;
                case 'extreme':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 15;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 50;
                    break;
                default:
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 1;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 10;
            }
        }

        // ボス情報を special_actions から抽出
        if (!isset($dungeonData['boss']) && isset($dungeonData['special_actions'])) {
            foreach ($dungeonData['special_actions'] as $action) {
                if (isset($action['type']) && $action['type'] === 'boss_battle' && isset($action['data']['boss'])) {
                    $dungeonData['boss'] = $action['data']['boss'];
                    break;
                }
            }
        }

        return $dungeonData;
    }

    /**
     * ID変更時の整合性チェック
     * 
     * @param string $oldId
     * @param string $newId
     * @param array $config
     * @return true|string true=成功, string=エラーメッセージ
     */
    private function validateAndProcessIdChange(string $oldId, string $newId, array $config)
    {
        // 新しいIDの重複チェック
        if (isset($config['pathways'][$newId])) {
            return "新しいID '{$newId}' は既に使用されています。";
        }

        // プレイヤーの現在位置チェック
        $playersOnLocation = \App\Models\Player::where('location_id', $oldId)->count();
        if ($playersOnLocation > 0) {
            return "現在 {$playersOnLocation} 人のプレイヤーがこの場所にいるため、IDを変更できません。";
        }

        return true;
    }

    /**
     * ID変更時の関連データ更新
     * 
     * @param array $config
     * @param string $oldId
     * @param string $newId
     * @return array 更新されたconfig
     */
    private function updateRelatedDataForIdChange(array $config, string $oldId, string $newId): array
    {
        // 他のpathwayの接続情報更新
        foreach ($config['pathways'] as $pathwayId => &$pathway) {
            if ($pathwayId === $oldId) continue;
            
            // connections更新
            if (isset($pathway['connections'])) {
                foreach (['start', 'end'] as $side) {
                    if (isset($pathway['connections'][$side]['id']) && 
                        $pathway['connections'][$side]['id'] === $oldId) {
                        $pathway['connections'][$side]['id'] = $newId;
                    }
                }
            }
            
            // branches更新
            if (isset($pathway['branches'])) {
                foreach ($pathway['branches'] as $position => &$branches) {
                    foreach ($branches as $direction => &$branch) {
                        if (isset($branch['id']) && $branch['id'] === $oldId) {
                            $branch['id'] = $newId;
                        }
                    }
                }
            }
        }

        // 町の接続情報更新
        if (isset($config['towns'])) {
            foreach ($config['towns'] as $townId => &$town) {
                if (isset($town['connections'])) {
                    foreach ($town['connections'] as $direction => &$connection) {
                        if (isset($connection['id']) && $connection['id'] === $oldId &&
                            in_array($connection['type'] ?? '', ['road', 'dungeon', 'pathway'])) {
                            $connection['id'] = $newId;
                        }
                    }
                }
            }
        }

        // プレイヤーデータ更新（念のため）
        \App\Models\Player::where('location_id', $oldId)->update(['location_id' => $newId]);

        return $config;
    }
}