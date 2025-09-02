<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\DungeonDesc;
use App\Models\Route;
use App\Services\Admin\AdminAuditService;
use App\Services\Admin\DungeonService;
use App\Http\Requests\Admin\AttachFloorsRequest;
use App\Http\Requests\Admin\DungeonDescFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminDungeonController extends AdminController
{
    private DungeonService $dungeonService;

    public function __construct(AdminAuditService $auditService, DungeonService $dungeonService)
    {
        parent::__construct($auditService);
        $this->dungeonService = $dungeonService;
    }

    /**
     * ダンジョン一覧表示（DungeonDescベース）
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('dungeons.index');

        try {
            $includeInactive = $request->boolean('include_inactive', false);
            $searchQuery = $request->get('search', '');

            $query = DungeonDesc::query();
            
            // アクティブフィルタリング
            if (!$includeInactive) {
                $query->active();
            }

            // 検索機能
            if ($searchQuery) {
                $query->where(function($q) use ($searchQuery) {
                    $q->where('dungeon_name', 'LIKE', '%' . $searchQuery . '%')
                      ->orWhere('dungeon_id', 'LIKE', '%' . $searchQuery . '%');
                });
            }

            $dungeons = $query->with(['floors' => function($query) {
                                $query->orderBy('name');
                            }])
                            ->withCount('floors')
                            ->orderBy('dungeon_name')
                            ->paginate(20)
                            ->appends($request->query());

            // 全体統計を取得する
            $totalStats = $this->dungeonService->getDungeonStatistics($includeInactive, $searchQuery);

            $this->auditLog('dungeons.index.viewed', [
                'result_count' => $dungeons->count(),
                'include_inactive' => $includeInactive,
                'search_query' => $searchQuery
            ]);

            return view('admin.dungeons.index', compact(
                'dungeons', 
                'includeInactive', 
                'searchQuery', 
                'totalStats'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load dungeons data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.dungeons.index', [
                'error' => 'ダンジョンデータの読み込みに失敗しました: ' . $e->getMessage(),
                'dungeons' => collect(),
                'includeInactive' => false,
                'searchQuery' => '',
                'totalStats' => []
            ]);
        }
    }

    /**
     * ダンジョン詳細表示（フロア一覧付き）
     */
    public function show(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $dungeon = DungeonDesc::with(['floors' => function($query) {
                                      $query->orderBy('name');
                                  }])
                                  ->find((int) $id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            $this->auditLog('dungeons.show.viewed', [
                'dungeon_id' => $id,
                'dungeon_name' => $dungeon->dungeon_name
            ]);

            return view('admin.dungeons.show', compact('dungeon'));

        } catch (\Exception $e) {
            Log::error('Failed to load dungeon detail', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.dungeons.index')
                           ->with('error', 'ダンジョン詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン作成フォーム
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        return view('admin.dungeons.create');
    }

    /**
     * ダンジョン作成処理
     */
    public function store(DungeonDescFormRequest $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        $this->trackPageAccess('dungeons.store');

        try {
            $validated = $request->getValidatedDataForCreate();
            
            $dungeon = DungeonDesc::create($validated);

            $this->auditLog('dungeons.created', [
                'dungeon_id' => $dungeon->id,
                'dungeon_name' => $dungeon->dungeon_name,
                'data' => $validated
            ], 'medium');

            return redirect()->route('admin.dungeons.show', $dungeon->id)
                           ->with('success', "ダンジョン「{$dungeon->dungeon_name}」が正常に作成されました。");

        } catch (\Exception $e) {
            Log::error('Failed to create dungeon', [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()
                        ->with('error', 'ダンジョンの作成に失敗しました。しばらくしてから再度お試しください。');
        }
    }

    /**
     * ダンジョン編集フォーム
     */
    public function edit(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $dungeon = DungeonDesc::find($id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            return view('admin.dungeons.edit', compact('dungeon'));

        } catch (\Exception $e) {
            Log::error('Failed to load dungeon for edit', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.dungeons.index')
                           ->with('error', 'ダンジョンの編集画面読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン更新処理
     */
    public function update(DungeonDescFormRequest $request, string $id)
    {
        $this->initializeForRequest();
        $this->trackPageAccess('dungeons.update');

        try {
            $dungeon = DungeonDesc::find($id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', '指定されたダンジョンが見つかりませんでした。');
            }

            $originalData = $dungeon->toArray();
            $validated = $request->getValidatedDataForUpdate();
            
            $dungeon->update($validated);

            $this->auditLog('dungeons.updated', [
                'dungeon_id' => $dungeon->id,
                'dungeon_name' => $dungeon->dungeon_name,
                'original_data' => $originalData,
                'updated_data' => $validated,
                'changes' => $dungeon->getChanges()
            ], 'medium');

            return redirect()->route('admin.dungeons.show', $dungeon->id)
                           ->with('success', "ダンジョン「{$dungeon->dungeon_name}」が正常に更新されました。");

        } catch (\Exception $e) {
            Log::error('Failed to update dungeon', [
                'dungeon_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()
                        ->with('error', 'ダンジョンの更新に失敗しました。しばらくしてから再度お試しください。');
        }
    }

    /**
     * ダンジョン削除処理（関連フロアはdungeon_id=nullにセット）
     */
    public function destroy(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $dungeon = DungeonDesc::find($id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            $dungeonName = $dungeon->dungeon_name;
            
            // トランザクションで安全に削除
            DB::transaction(function () use ($dungeon) {
                // 関連フロアのdungeon_idをnullに設定
                Route::where('dungeon_id', $dungeon->dungeon_id)->update([
                    'dungeon_id' => null
                ]);
                
                // ダンジョンマスター情報を削除
                $dungeon->delete();
            });

            $this->auditLog('dungeons.deleted', [
                'dungeon_id' => $id,
                'dungeon_name' => $dungeonName
            ]);

            return redirect()->route('admin.dungeons.index')
                           ->with('success', "ダンジョン '{$dungeonName}' が正常に削除されました。");

        } catch (\Exception $e) {
            Log::error('Failed to delete dungeon', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.dungeons.index')
                           ->with('error', 'ダンジョンの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 特定ダンジョンのフロア管理画面
     */
    public function floors(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $dungeon = DungeonDesc::with(['floors' => function($query) {
                                      $query->orderBy('name');
                                  }])
                                  ->find((int) $id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            $this->auditLog('dungeons.floors.viewed', [
                'dungeon_id' => $id,
                'dungeon_name' => $dungeon->dungeon_name
            ]);

            return view('admin.dungeons.floors', compact('dungeon'));

        } catch (\Exception $e) {
            Log::error('Failed to load dungeon floors', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.dungeons.index')
                           ->with('error', 'ダンジョンフロアの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 新フロア作成フォーム
     */
    public function createFloor(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $dungeon = DungeonDesc::find($id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            return view('admin.dungeons.create-floor', compact('dungeon'));

        } catch (\Exception $e) {
            Log::error('Failed to load create floor form', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.dungeons.floors', $id)
                           ->with('error', 'フロア作成画面の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 新フロア作成処理
     */
    public function storeFloor(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $dungeon = DungeonDesc::find($id);

            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            $validated = $request->validate([
                'floor_suffix' => 'required|string|regex:/^[a-zA-Z0-9_]+$/',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'length' => 'required|integer|min:1|max:1000',
                'encounter_rate' => 'nullable|numeric|between:0,1',
            ]);

            // floor_suffixからidを生成
            $floorId = $dungeon->dungeon_id . '_' . $validated['floor_suffix'];
            
            // IDの重複チェック
            if (Route::where('id', $floorId)->exists()) {
                return redirect()->back()
                               ->withErrors(['floor_suffix' => 'このフロアIDは既に使用されています。'])
                               ->withInput();
            }

            // validatedからfloor_suffixを削除してidに置き換え
            unset($validated['floor_suffix']);
            $validated['id'] = $floorId;

            $floor = Route::create(array_merge($validated, [
                'category' => 'dungeon',
                'dungeon_id' => $dungeon->dungeon_id,
                'is_active' => true,
            ]));

            $this->auditLog('dungeons.floors.created', [
                'dungeon_id' => $id,
                'floor_id' => $floor->id,
                'floor_name' => $floor->name
            ]);

            return redirect()->route('admin.dungeons.floors', $id)
                           ->with('success', 'フロアが正常に作成されました。');

        } catch (\Exception $e) {
            Log::error('Failed to create dungeon floor', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'フロアの作成に失敗しました: ' . $e->getMessage());
        }
    }


    /**
     * フロアアタッチフォーム表示（既存フロアをこの親にアタッチ）
     */
    public function attachFloorsForm(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $dungeon = DungeonDesc::find($id);
            if (!$dungeon) {
                return redirect()->route('admin.dungeons.index')
                               ->with('error', 'ダンジョンが見つかりませんでした。');
            }

            $searchQuery = $request->get('search', '');
            $onlyOrphans = $request->boolean('only_orphans', true);

            // サービスを使用して候補フロアを検索
            $candidates = $this->dungeonService->searchCandidateFloors(
                $dungeon->dungeon_id,
                $searchQuery,
                $onlyOrphans,
                10
            );
            $candidates->appends($request->query());

            return response()->json([
                'success' => true,
                'html' => view('admin.dungeons.partials.attach-floors-form', [
                    'dungeon' => $dungeon,
                    'candidates' => $candidates,
                    'searchQuery' => $searchQuery,
                    'onlyOrphans' => $onlyOrphans
                ])->render()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load attach floors form', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'アタッチフォームの読み込みに失敗しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * フロアアタッチ実行
     */
    public function attachFloors(AttachFloorsRequest $request, string $id)
    {
        $this->initializeForRequest();

        try {
            $dungeon = DungeonDesc::find($id);
            if (!$dungeon) {
                return response()->json([
                    'success' => false,
                    'error' => 'ダンジョンが見つかりませんでした。'
                ]);
            }

            $validated = $request->validated();
            
            // サービスを使用してアタッチ実行
            $result = $this->dungeonService->attachFloorsToParent(
                $dungeon->dungeon_id,
                $validated['floor_ids']
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }

            $this->auditLog('dungeons.floors.attached', [
                'dungeon_id' => $id,
                'dungeon_name' => $dungeon->dungeon_name,
                'attached_floor_ids' => $validated['floor_ids'],
                'count' => $result['updated_count']
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$result['updated_count']}個のフロアを '{$dungeon->dungeon_name}' にアタッチしました。",
                'redirect' => route('admin.dungeons.floors', $id)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to attach floors', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'フロアのアタッチに失敗しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * オーファンフロア管理ページ
     */
    public function orphans(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('dungeons.orphans');

        try {
            // サービスを使用してオーファンフロアを検出
            $orphanData = $this->dungeonService->detectOrphanFloors();

            // ページネーション用に変換
            $orphanFloors = Route::where('category', 'dungeon')
                                ->whereNull('dungeon_id')
                                ->orderBy('name')
                                ->paginate(20, ['*'], 'orphans');

            $missingParentFloors = Route::where('category', 'dungeon')
                                      ->whereNotNull('dungeon_id')
                                      ->whereNotIn('dungeon_id', 
                                          DungeonDesc::pluck('dungeon_id')->toArray()
                                      )
                                      ->orderBy('name')
                                      ->paginate(20, ['*'], 'missing');

            // 利用可能な親ダンジョン一覧
            $availableParents = DungeonDesc::active()->orderBy('dungeon_name')->get();

            $this->auditLog('dungeons.orphans.viewed', [
                'orphan_count' => $orphanFloors->total(),
                'missing_parent_count' => $missingParentFloors->total(),
                'total_issues' => $orphanData['total_issues']
            ]);

            return view('admin.dungeons.orphans', compact(
                'orphanFloors',
                'missingParentFloors', 
                'availableParents'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load orphan floors data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.dungeons.orphans', [
                'error' => 'オーファンフロアデータの読み込みに失敗しました: ' . $e->getMessage(),
                'orphanFloors' => collect(),
                'missingParentFloors' => collect(),
                'availableParents' => collect()
            ]);
        }
    }

    /**
     * オーファンフロアの一括処理
     */
    public function processOrphans(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'action' => 'required|in:attach_to_existing,create_new_parent',
            'floor_ids' => 'required|array|min:1',
            'floor_ids.*' => 'exists:routes,id',
            'target_dungeon_id' => 'nullable|exists:dungeons_desc,id',
            'new_dungeon_id' => 'nullable|string|max:255|unique:dungeons_desc,dungeon_id',
            'new_dungeon_name' => 'nullable|string|max:255',
            'new_dungeon_desc' => 'nullable|string'
        ]);

        try {
            $action = $validated['action'];
            $floorIds = $validated['floor_ids'];
            
            if ($action === 'attach_to_existing') {
                // 既存の親にアタッチ
                if (!$validated['target_dungeon_id']) {
                    return back()->withInput()
                                ->with('error', 'アタッチ先のダンジョンを選択してください。');
                }
                
                $result = $this->dungeonService->attachOrphansToExistingParent(
                    $validated['target_dungeon_id'],
                    $floorIds
                );
                
                if (!$result['success']) {
                    return back()->withInput()
                                ->with('error', $result['error']);
                }
                
                $this->auditLog('dungeons.orphans.attached_to_existing', [
                    'target_dungeon_id' => $result['parent']->id,
                    'target_dungeon_name' => $result['parent']->dungeon_name,
                    'floor_ids' => $floorIds,
                    'count' => $result['updated_count']
                ]);
                
                $message = "{$result['updated_count']}個のフロアを'{$result['parent']->dungeon_name}'にアタッチしました。";
                
            } else if ($action === 'create_new_parent') {
                // 新しい親を作成してアタッチ
                if (!$validated['new_dungeon_id'] || !$validated['new_dungeon_name']) {
                    return back()->withInput()
                                ->with('error', '新しいダンジョンのIDと名前を入力してください。');
                }
                
                $parentData = [
                    'dungeon_id' => $validated['new_dungeon_id'],
                    'dungeon_name' => $validated['new_dungeon_name'],
                    'dungeon_desc' => $validated['new_dungeon_desc'] ?? null
                ];
                
                $result = $this->dungeonService->createParentAndAttachOrphans(
                    $parentData,
                    $floorIds
                );
                
                if (!$result['success']) {
                    return back()->withInput()
                                ->with('error', $result['error']);
                }
                
                $this->auditLog('dungeons.orphans.attached_to_new', [
                    'new_dungeon_id' => $result['parent']->id,
                    'new_dungeon_name' => $result['parent']->dungeon_name,
                    'floor_ids' => $floorIds,
                    'count' => $result['updated_count']
                ]);
                
                $message = "新しいダンジョン'{$result['parent']->dungeon_name}'を作成し、{$result['updated_count']}個のフロアをアタッチしました。";
            }

            return redirect()->route('admin.dungeons.orphans')
                           ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to process orphan floors', [
                'validated_data' => $validated,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'オーファンフロアの処理に失敗しました: ' . $e->getMessage());
        }
    }
}
