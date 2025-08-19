<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Monster\MonsterConfigService;
use App\Services\Location\LocationConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminMonsterSpawnController extends Controller
{
    use \App\Http\Controllers\Admin\Traits\HasAdminBase;

    private MonsterConfigService $monsterConfigService;
    private LocationConfigService $locationConfigService;

    public function __construct(
        MonsterConfigService $monsterConfigService,
        LocationConfigService $locationConfigService
    ) {
        $this->monsterConfigService = $monsterConfigService;
        $this->locationConfigService = $locationConfigService;
    }

    /**
     * モンスター出現管理一覧
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.monster_spawns');

        $filters = $request->only(['search', 'pathway_id', 'sort_by', 'sort_direction']);

        try {
            $locationConfig = $this->locationConfigService->loadUnifiedConfig();
            $pathways = $locationConfig['pathways'] ?? [];
            
            // フィルタリング
            if (!empty($filters['pathway_id'])) {
                $pathways = array_filter($pathways, function($pathwayId) use ($filters) {
                    return $pathwayId === $filters['pathway_id'];
                }, ARRAY_FILTER_USE_KEY);
            }

            if (!empty($filters['search'])) {
                $pathways = array_filter($pathways, function($pathway, $pathwayId) use ($filters) {
                    return stripos($pathway['name'], $filters['search']) !== false ||
                           stripos($pathwayId, $filters['search']) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            // 各pathwayのmonster spawn設定を取得
            $pathwaySpawns = [];
            foreach ($pathways as $pathwayId => $pathway) {
                $spawns = $this->monsterConfigService->getMonsterSpawnsForPathway($pathwayId);
                $validation = $this->monsterConfigService->validatePathwaySpawns($pathwayId);
                
                $pathwaySpawns[$pathwayId] = [
                    'pathway' => $pathway,
                    'spawns' => $spawns,
                    'validation' => $validation
                ];
            }

            return view('admin.monster_spawns.index', [
                'pathwaySpawns' => $pathwaySpawns,
                'filters' => $filters,
                'pathways' => $locationConfig['pathways'] ?? [],
                'total_pathways' => count($locationConfig['pathways'] ?? []),
                'filtered_pathways' => count($pathwaySpawns)
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 特定のpathwayのmonster spawn管理
     */
    public function pathwaySpawns(Request $request, string $pathwayId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $locationConfig = $this->locationConfigService->loadUnifiedConfig();
            
            if (!isset($locationConfig['pathways'][$pathwayId])) {
                return redirect()->route('admin.monster_spawns.index')
                    ->with('error', '指定されたpathwayが見つかりません');
            }

            $pathway = $locationConfig['pathways'][$pathwayId];
            $spawns = $this->monsterConfigService->getMonsterSpawnsForPathway($pathwayId);
            $monsters = $this->monsterConfigService->getActiveMonsters();
            $validation = $this->monsterConfigService->validatePathwaySpawns($pathwayId);

            return view('admin.monster_spawns.pathway', [
                'pathwayId' => $pathwayId,
                'pathway' => $pathway,
                'spawns' => $spawns,
                'monsters' => $monsters,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * monster spawn設定保存
     */
    public function saveSpawns(Request $request, string $pathwayId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $rules = [
            'spawns' => 'required|array',
            'spawns.*.monster_id' => 'required|string',
            'spawns.*.spawn_rate' => 'required|numeric|min:0|max:1',
            'spawns.*.priority' => 'integer|min:0',
            'spawns.*.is_active' => 'boolean',
            'spawns.*.min_level' => 'nullable|integer|min:1',
            'spawns.*.max_level' => 'nullable|integer|min:1'
        ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $spawns = [];
            $monsters = $this->monsterConfigService->getActiveMonsters();
            
            foreach ($request->input('spawns', []) as $index => $spawnData) {
                $monsterId = $spawnData['monster_id'];
                
                // モンスターの存在確認
                if (!isset($monsters[$monsterId])) {
                    return redirect()->back()
                        ->with('error', "モンスター '{$monsterId}' が見つかりません")
                        ->withInput();
                }

                $spawns[$monsterId] = [
                    'monster_id' => $monsterId,
                    'spawn_rate' => (float) $spawnData['spawn_rate'],
                    'priority' => (int) ($spawnData['priority'] ?? 0),
                    'min_level' => !empty($spawnData['min_level']) ? (int) $spawnData['min_level'] : null,
                    'max_level' => !empty($spawnData['max_level']) ? (int) $spawnData['max_level'] : null,
                    'is_active' => (bool) ($spawnData['is_active'] ?? true)
                ];
            }

            // 出現率の合計チェック
            $totalRate = array_sum(array_column($spawns, 'spawn_rate'));
            if ($totalRate > 1.0) {
                return redirect()->back()
                    ->with('error', "出現率の合計が100%を超えています (" . number_format($totalRate * 100, 1) . "%)")
                    ->withInput();
            }

            // 設定保存 - 新構造対応
            $locationConfig = $this->locationConfigService->loadUnifiedConfig();
            if (!isset($locationConfig['pathways'][$pathwayId]['spawn_list_id'])) {
                return redirect()->back()
                    ->with('error', 'このPathwayには出現リストIDが設定されていません')
                    ->withInput();
            }

            $spawnListId = $locationConfig['pathways'][$pathwayId]['spawn_list_id'];
            $spawnLists = $this->monsterConfigService->loadSpawnLists();
            
            if (!isset($spawnLists[$spawnListId])) {
                return redirect()->back()
                    ->with('error', "出現リスト '{$spawnListId}' が見つかりません")
                    ->withInput();
            }

            // 出現リストのモンスター設定を更新
            $spawnLists[$spawnListId]['monsters'] = $spawns;
            $spawnLists[$spawnListId]['updated_at'] = now()->toISOString();

            if (!$this->monsterConfigService->saveSpawnLists($spawnLists)) {
                return redirect()->back()
                    ->with('error', '設定の保存に失敗しました')
                    ->withInput();
            }

            // 監査ログ記録
            $this->auditLog('monster_spawns.updated', [
                'pathway_id' => $pathwayId,
                'spawn_list_id' => $spawnListId,
                'spawns_count' => count($spawns),
                'total_rate' => $totalRate
            ]);

            return redirect()->route('admin.monster_spawns.pathway', $pathwayId)
                ->with('success', 'モンスター出現設定を保存しました');

        } catch (\Exception $e) {
            Log::error('Failed to save monster spawns', [
                'pathway_id' => $pathwayId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', '保存に失敗しました: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * 特定のmonster spawn削除
     */
    public function removeSpawn(Request $request, string $pathwayId, string $monsterId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $locationConfig = $this->locationConfigService->loadUnifiedConfig();
            if (!isset($locationConfig['pathways'][$pathwayId]['spawn_list_id'])) {
                return redirect()->back()->with('error', 'このPathwayには出現リストIDが設定されていません');
            }

            $spawnListId = $locationConfig['pathways'][$pathwayId]['spawn_list_id'];
            $spawnLists = $this->monsterConfigService->loadSpawnLists();
            
            if (!isset($spawnLists[$spawnListId])) {
                return redirect()->back()->with('error', "出現リスト '{$spawnListId}' が見つかりません");
            }

            if (!isset($spawnLists[$spawnListId]['monsters'][$monsterId])) {
                return redirect()->back()->with('error', '指定されたモンスター出現設定が見つかりません');
            }

            unset($spawnLists[$spawnListId]['monsters'][$monsterId]);
            $spawnLists[$spawnListId]['updated_at'] = now()->toISOString();

            if (!$this->monsterConfigService->saveSpawnLists($spawnLists)) {
                return redirect()->back()->with('error', '削除に失敗しました');
            }

            // 監査ログ記録
            $this->auditLog('monster_spawns.removed', [
                'pathway_id' => $pathwayId,
                'spawn_list_id' => $spawnListId,
                'monster_id' => $monsterId
            ]);

            return redirect()->back()->with('success', 'モンスター出現設定を削除しました');

        } catch (\Exception $e) {
            Log::error('Failed to remove monster spawn', [
                'pathway_id' => $pathwayId,
                'monster_id' => $monsterId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->with('error', '削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 全pathwayのvalidation結果を取得
     */
    public function validateAll(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $results = $this->monsterConfigService->validateAllPathwaySpawns();
            
            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * テスト用monster選択
     */
    public function testSpawn(Request $request, string $pathwayId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $monster = $this->monsterConfigService->getRandomMonsterForPathway($pathwayId);
            
            return response()->json([
                'success' => true,
                'monster' => $monster,
                'pathway_id' => $pathwayId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
