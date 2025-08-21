<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminAuditService;
use App\Models\Route;
use App\Models\MonsterSpawnList;
use App\Models\Monster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * モンスタースポーン管理コントローラー（統合版）
 * 
 * 新しいMonsterSpawnListテーブルを使用したスポーン設定管理
 */
class AdminMonsterSpawnController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
    }

    /**
     * モンスタースポーン管理トップページ
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.view');
        $this->trackPageAccess('monster-spawns.index');

        $filters = $request->only(['location_search', 'monster_search', 'category', 'is_active']);

        try {
            // Location別のスポーン情報を取得
            $query = Route::with(['monsterSpawns.monster'])
                                ->whereIn('category', ['road', 'dungeon']);

            // フィルタリング
            if (!empty($filters['location_search'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['location_search'] . '%')
                      ->orWhere('id', 'like', '%' . $filters['location_search'] . '%');
                });
            }

            if (!empty($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            $locations = $query->get();

            // モンスターフィルタリング（MonsterSpawn側）
            if (!empty($filters['monster_search']) || isset($filters['is_active'])) {
                $locations = $locations->filter(function($location) use ($filters) {
                    $spawns = $location->monsterSpawns;
                    
                    if (!empty($filters['monster_search'])) {
                        $spawns = $spawns->filter(function($spawn) use ($filters) {
                            return stripos($spawn->monster->name, $filters['monster_search']) !== false;
                        });
                    }

                    if (isset($filters['is_active'])) {
                        $spawns = $spawns->where('is_active', (bool)$filters['is_active']);
                    }

                    return $spawns->count() > 0;
                });
            }

            // 統計情報
            $stats = [
                'total_locations' => Route::whereIn('category', ['road', 'dungeon'])->count(),
                'locations_with_spawns' => Route::whereHas('monsterSpawns')->count(),
                'total_spawns' => MonsterSpawnList::count(),
                'active_spawns' => MonsterSpawnList::where('is_active', true)->count(),
                'unique_monsters' => MonsterSpawnList::distinct('monster_id')->count(),
            ];

            $this->auditLog('monster_spawns.index.viewed', [
                'filters' => $filters,
                'locations_count' => $locations->count(),
                'stats' => $stats
            ]);

            return view('admin.monster-spawns.index', compact('locations', 'filters', 'stats'));

        } catch (\Exception $e) {
            Log::error('Failed to load monster spawn data', [
                'error' => $e->getMessage()
            ]);

            return view('admin.monster-spawns.index', [
                'error' => 'モンスタースポーンデータの読み込みに失敗しました: ' . $e->getMessage(),
                'locations' => collect(),
                'filters' => $filters,
                'stats' => []
            ]);
        }
    }

    /**
     * 特定ロケーションのスポーン詳細表示
     */
    public function show(Request $request, string $locationId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.view');

        try {
            $location = Route::with(['monsterSpawns.monster'])->find($locationId);

            if (!$location) {
                return redirect()->route('admin.monster-spawns.index')
                               ->with('error', 'ロケーションが見つかりません。');
            }

            // スポーン設定の統計・検証
            $spawnStats = $location->getSpawnStats();
            $validationIssues = $location->validateSpawnConfiguration();

            $this->auditLog('monster_spawns.show.viewed', [
                'location_id' => $locationId,
                'location_name' => $location->name,
                'spawn_count' => $location->monsterSpawns->count()
            ]);

            return view('admin.monster-spawns.show', compact('location', 'spawnStats', 'validationIssues'));

        } catch (\Exception $e) {
            Log::error('Failed to load location spawn details', [
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.monster-spawns.index')
                           ->with('error', 'スポーン詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 新規スポーン追加フォーム
     */
    public function create(Request $request, string $locationId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.create');

        try {
            $location = Route::find($locationId);

            if (!$location) {
                return redirect()->route('admin.monster-spawns.index')
                               ->with('error', 'ロケーションが見つかりません。');
            }

            // 使用可能なモンスター（まだスポーン設定されていないもの）
            $existingMonsterIds = $location->monsterSpawns->pluck('monster_id')->toArray();
            $availableMonsters = Monster::whereNotIn('id', $existingMonsterIds)
                                       ->where('is_active', true)
                                       ->orderBy('name')
                                       ->get();

            // 次の優先度
            $nextPriority = $location->monsterSpawns->max('priority') + 1;

            return view('admin.monster-spawns.create', compact('location', 'availableMonsters', 'nextPriority'));

        } catch (\Exception $e) {
            Log::error('Failed to load monster spawn create form', [
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.monster-spawns.index')
                           ->with('error', 'スポーン作成フォームの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * スポーン設定保存
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.create');

        $validator = $this->validateSpawnData($request);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $spawn = MonsterSpawnList::create([
                'location_id' => $request->location_id,
                'monster_id' => $request->monster_id,
                'spawn_rate' => $request->spawn_rate,
                'priority' => $request->priority,
                'min_level' => $request->min_level ?: null,
                'max_level' => $request->max_level ?: null,
                'is_active' => (bool)$request->is_active,
            ]);

            $this->auditLog('monster_spawns.created', [
                'spawn_id' => $spawn->id,
                'location_id' => $spawn->location_id,
                'monster_id' => $spawn->monster_id,
                'spawn_data' => $spawn->toArray()
            ], 'high');

            return redirect()->route('admin.monster-spawns.show', $request->location_id)
                           ->with('success', 'モンスタースポーンが追加されました。');

        } catch (\Exception $e) {
            $this->auditLog('monster_spawns.create.failed', [
                'location_id' => $request->location_id,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ], 'critical');

            return back()->withError('スポーン設定の保存に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * スポーン編集フォーム
     */
    public function edit(Request $request, int $spawnId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');

        try {
            $spawn = MonsterSpawnList::with(['gameLocation', 'monster'])->find($spawnId);

            if (!$spawn) {
                return redirect()->route('admin.monster-spawns.index')
                               ->with('error', 'モンスタースポーンが見つかりません。');
            }

            // 利用可能なモンスター（現在選択中は含める）
            $existingMonsterIds = $spawn->gameLocation->monsterSpawns
                                        ->where('id', '!=', $spawn->id)
                                        ->pluck('monster_id')
                                        ->toArray();
            
            $availableMonsters = Monster::where(function($q) use ($spawn, $existingMonsterIds) {
                $q->whereNotIn('id', $existingMonsterIds)
                  ->orWhere('id', $spawn->monster_id);
            })->where('is_active', true)->orderBy('name')->get();

            return view('admin.monster-spawns.edit', compact('spawn', 'availableMonsters'));

        } catch (\Exception $e) {
            Log::error('Failed to load monster spawn edit form', [
                'spawn_id' => $spawnId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.monster-spawns.index')
                           ->with('error', 'スポーン編集フォームの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * スポーン設定更新
     */
    public function update(Request $request, int $spawnId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');

        $validator = $this->validateSpawnData($request, $spawnId);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $spawn = MonsterSpawnList::find($spawnId);

            if (!$spawn) {
                return back()->withError('モンスタースポーンが見つかりません。')->withInput();
            }

            $originalData = $spawn->toArray();

            $spawn->update([
                'monster_id' => $request->monster_id,
                'spawn_rate' => $request->spawn_rate,
                'priority' => $request->priority,
                'min_level' => $request->min_level ?: null,
                'max_level' => $request->max_level ?: null,
                'is_active' => (bool)$request->is_active,
            ]);

            $this->auditLog('monster_spawns.updated', [
                'spawn_id' => $spawn->id,
                'location_id' => $spawn->location_id,
                'original_data' => $originalData,
                'updated_data' => $spawn->fresh()->toArray()
            ], 'high');

            return redirect()->route('admin.monster-spawns.show', $spawn->location_id)
                           ->with('success', 'モンスタースポーンが更新されました。');

        } catch (\Exception $e) {
            $this->auditLog('monster_spawns.update.failed', [
                'spawn_id' => $spawnId,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ], 'critical');

            return back()->withError('スポーン設定の更新に失敗しました: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * スポーン設定削除
     */
    public function destroy(Request $request, int $spawnId)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.delete');

        try {
            $spawn = MonsterSpawnList::with(['gameLocation', 'monster'])->find($spawnId);

            if (!$spawn) {
                return back()->withError('モンスタースポーンが見つかりません。');
            }

            $locationId = $spawn->location_id;
            $deletedData = $spawn->toArray();

            $spawn->delete();

            $this->auditLog('monster_spawns.deleted', [
                'deleted_spawn' => $deletedData
            ], 'critical');

            return redirect()->route('admin.monster-spawns.show', $locationId)
                           ->with('success', 'モンスタースポーンが削除されました。');

        } catch (\Exception $e) {
            $this->auditLog('monster_spawns.delete.failed', [
                'spawn_id' => $spawnId,
                'error' => $e->getMessage()
            ], 'critical');

            return back()->withError('スポーン設定の削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 一括操作
     */
    public function bulkAction(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.edit');

        $action = $request->input('action');
        $spawnIds = $request->input('spawn_ids', []);

        if (empty($spawnIds)) {
            return back()->withError('操作対象が選択されていません。');
        }

        try {
            $affectedCount = 0;

            switch ($action) {
                case 'activate':
                    $affectedCount = MonsterSpawnList::whereIn('id', $spawnIds)->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $affectedCount = MonsterSpawnList::whereIn('id', $spawnIds)->update(['is_active' => false]);
                    break;

                case 'delete':
                    if (!$this->hasPermission('monsters.delete')) {
                        return back()->withError('削除権限がありません。');
                    }
                    $affectedCount = MonsterSpawnList::whereIn('id', $spawnIds)->delete();
                    break;

                default:
                    return back()->withError('無効な操作です。');
            }

            $this->auditLog('monster_spawns.bulk_action', [
                'action' => $action,
                'spawn_ids' => $spawnIds,
                'affected_count' => $affectedCount
            ], 'medium');

            return back()->with('success', "{$affectedCount}件のスポーン設定を{$action}しました。");

        } catch (\Exception $e) {
            $this->auditLog('monster_spawns.bulk_action.failed', [
                'action' => $action,
                'spawn_ids' => $spawnIds,
                'error' => $e->getMessage()
            ], 'critical');

            return back()->withError('一括操作に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * スポーンデータのバリデーション
     */
    private function validateSpawnData(Request $request, ?int $excludeSpawnId = null): \Illuminate\Validation\Validator
    {
        $rules = [
            'location_id' => 'required|string|exists:routes,id',
            'monster_id' => 'required|string|exists:monsters,id',
            'spawn_rate' => 'required|numeric|min:0|max:1',
            'priority' => 'required|integer|min:0|max:999',
            'min_level' => 'nullable|integer|min:1|max:999',
            'max_level' => 'nullable|integer|min:1|max:999',
            'is_active' => 'boolean',
        ];

        // 重複チェック
        $uniqueRule = Rule::unique('monster_spawn_lists')->where(function ($query) use ($request) {
            return $query->where('location_id', $request->location_id)
                         ->where('monster_id', $request->monster_id);
        });

        if ($excludeSpawnId) {
            $uniqueRule->ignore($excludeSpawnId);
        }

        $rules['monster_id'] = [$rules['monster_id'], $uniqueRule];

        $validator = Validator::make($request->all(), $rules, [
            'spawn_rate.max' => '出現率は1.0（100%）以下で入力してください。',
            'min_level.min' => '最小レベルは1以上で入力してください。',
            'max_level.min' => '最大レベルは1以上で入力してください。',
            'monster_id.unique' => 'このロケーションには既に同じモンスターのスポーン設定があります。',
        ]);

        // カスタムバリデーション
        $validator->after(function ($validator) use ($request) {
            // レベル範囲チェック
            if ($request->min_level && $request->max_level && $request->min_level > $request->max_level) {
                $validator->errors()->add('max_level', '最大レベルは最小レベル以上で入力してください。');
            }

            // 出現率合計チェック（他のスポーンとの合計）
            $existingSpawns = MonsterSpawnList::where('location_id', $request->location_id);
            if ($request->route('spawn_id')) {
                $existingSpawns->where('id', '!=', $request->route('spawn_id'));
            }
            
            $totalRate = $existingSpawns->sum('spawn_rate') + ($request->spawn_rate ?? 0);
            if ($totalRate > 1.0) {
                $validator->errors()->add('spawn_rate', 'このロケーションの総出現率が100%を超えます。');
            }
        });

        return $validator;
    }
}