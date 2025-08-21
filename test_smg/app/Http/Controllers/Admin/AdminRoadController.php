<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Route;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminRoadController extends AdminController
{
    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
    }

    /**
     * Road一覧表示
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('roads.index');

        try {
            $roads = Route::roads()
                                ->active()
                                ->orderBy('name')
                                ->paginate(20);

            $this->auditLog('roads.index.viewed', [
                'result_count' => $roads->count()
            ]);

            return view('admin.roads.index', compact('roads'));

        } catch (\Exception $e) {
            Log::error('Failed to load roads data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.roads.index', [
                'error' => 'Road データの読み込みに失敗しました: ' . $e->getMessage(),
                'roads' => collect()
            ]);
        }
    }

    /**
     * Road詳細表示
     */
    public function show(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $this->auditLog('roads.show.viewed', [
                'road_id' => $id,
                'road_name' => $road->name
            ]);

            return view('admin.roads.show', compact('road'));

        } catch (\Exception $e) {
            Log::error('Failed to load road detail', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road 詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road作成フォーム
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        return view('admin.roads.create');
    }

    /**
     * Road作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'id' => 'required|string|unique:routes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'length' => 'required|integer|min:1|max:1000',
            'difficulty' => 'required|in:easy,normal,hard',
            'encounter_rate' => 'nullable|numeric|between:0,1',
        ]);

        try {
            $road = Route::create(array_merge($validated, [
                'category' => 'road',
                'dungeon_id' => null,
                'is_active' => true,
            ]));

            $this->auditLog('roads.created', [
                'road_id' => $road->id,
                'road_name' => $road->name
            ]);

            return redirect()->route('admin.roads.show', $road->id)
                           ->with('success', 'Road が正常に作成されました。');

        } catch (\Exception $e) {
            Log::error('Failed to create road', [
                'data' => $validated,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'Road の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road編集フォーム
     */
    public function edit(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            return view('admin.roads.edit', compact('road'));

        } catch (\Exception $e) {
            Log::error('Failed to load road for edit', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road の編集画面読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road更新処理
     */
    public function update(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'length' => 'required|integer|min:1|max:1000',
                'difficulty' => 'required|in:easy,normal,hard',
                'encounter_rate' => 'nullable|numeric|between:0,1',
                'is_active' => 'boolean',
            ]);

            $road->update($validated);

            $this->auditLog('roads.updated', [
                'road_id' => $road->id,
                'road_name' => $road->name,
                'changes' => $road->getChanges()
            ]);

            return redirect()->route('admin.roads.show', $road->id)
                           ->with('success', 'Road が正常に更新されました。');

        } catch (\Exception $e) {
            Log::error('Failed to update road', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withInput()
                        ->with('error', 'Road の更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Road削除処理
     */
    public function destroy(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.delete');

        try {
            $road = Route::roads()->where('id', $id)->first();

            if (!$road) {
                return redirect()->route('admin.roads.index')
                               ->with('error', 'Road が見つかりませんでした。');
            }

            $roadName = $road->name;
            $road->delete();

            $this->auditLog('roads.deleted', [
                'road_id' => $id,
                'road_name' => $roadName
            ]);

            return redirect()->route('admin.roads.index')
                           ->with('success', "Road '{$roadName}' が正常に削除されました。");

        } catch (\Exception $e) {
            Log::error('Failed to delete road', [
                'road_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('admin.roads.index')
                           ->with('error', 'Road の削除に失敗しました: ' . $e->getMessage());
        }
    }
}
