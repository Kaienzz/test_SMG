<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\DungeonDesc;
use App\Models\Route;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminDungeonController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
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
            $dungeons = DungeonDesc::active()
                                  ->with(['floors' => function($query) {
                                      $query->active()->orderBy('name');
                                  }])
                                  ->withCount('floors')
                                  ->orderBy('dungeon_name')
                                  ->paginate(20);

            $this->auditLog('dungeons.index.viewed', [
                'result_count' => $dungeons->count()
            ]);

            return view('admin.dungeons.index', compact('dungeons'));

        } catch (\Exception $e) {
            Log::error('Failed to load dungeons data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.dungeons.index', [
                'error' => 'ダンジョンデータの読み込みに失敗しました: ' . $e->getMessage(),
                'dungeons' => collect()
            ]);
        }
    }

    /**
     * ダンジョン詳細表示（フロア一覧付き）
     */
    public function show(Request $request, int $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $dungeon = DungeonDesc::with(['floors' => function($query) {
                                      $query->orderBy('name');
                                  }])
                                  ->find($id);

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
        $this->checkPermission('locations.edit');

        return view('admin.dungeons.create');
    }

    /**
     * ダンジョン作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'dungeon_id' => 'required|string|unique:dungeons_desc,dungeon_id|max:255',
            'dungeon_name' => 'required|string|max:255',
            'dungeon_desc' => 'nullable|string',
        ]);

        try {
            $dungeon = DungeonDesc::create(array_merge($validated, [
                'is_active' => true,
            ]));

            $this->auditLog('dungeons.created', [
                'dungeon_id' => $dungeon->id,
                'dungeon_name' => $dungeon->dungeon_name
            ]);

            return redirect()->route('admin.dungeons.show', $dungeon->id)
                           ->with('success', 'ダンジョンが正常に作成されました。');

        } catch (\Exception $e) {
            Log::error('Failed to create dungeon', [
                'data' => $validated,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'ダンジョンの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン編集フォーム
     */
    public function edit(Request $request, int $id)
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
    public function update(Request $request, int $id)
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
                'dungeon_name' => 'required|string|max:255',
                'dungeon_desc' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            $dungeon->update($validated);

            $this->auditLog('dungeons.updated', [
                'dungeon_id' => $dungeon->id,
                'dungeon_name' => $dungeon->dungeon_name,
                'changes' => $dungeon->getChanges()
            ]);

            return redirect()->route('admin.dungeons.show', $dungeon->id)
                           ->with('success', 'ダンジョンが正常に更新されました。');

        } catch (\Exception $e) {
            Log::error('Failed to update dungeon', [
                'dungeon_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'ダンジョンの更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ダンジョン削除処理（関連フロアはdungeon_id=nullにセット）
     */
    public function destroy(Request $request, int $id)
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
    public function floors(Request $request, int $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $dungeon = DungeonDesc::with(['floors' => function($query) {
                                      $query->orderBy('name');
                                  }])
                                  ->find($id);

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
    public function createFloor(Request $request, int $id)
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
    public function storeFloor(Request $request, int $id)
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
                'id' => 'required|string|unique:routes,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'length' => 'required|integer|min:1|max:1000',
                'difficulty' => 'required|in:easy,normal,hard',
                'encounter_rate' => 'nullable|numeric|between:0,1',
            ]);

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
}
