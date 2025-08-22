<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Route;
use App\Models\RouteConnection;
use App\Services\Admin\AdminLocationService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminTownController extends AdminController
{
    private AdminLocationService $adminLocationService;

    public function __construct(AdminAuditService $auditService, AdminLocationService $adminLocationService)
    {
        parent::__construct($auditService);
        $this->adminLocationService = $adminLocationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('towns.index');

        $filters = $request->only(['search', 'sort_by', 'sort_direction']);
        
        try {
            $towns = $this->adminLocationService->getTowns($filters);

            $this->auditLog('towns.index.viewed', [
                'filters' => $filters,
                'result_count' => count($towns)
            ]);

            return view('admin.towns.index', compact(
                'towns',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load towns data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.towns.index', [
                'error' => '町データの読み込みに失敗しました: ' . $e->getMessage(),
                'towns' => [],
                'filters' => $filters
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        $this->trackPageAccess('towns.create');

        return view('admin.towns.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'id' => 'required|string|max:255|unique:routes,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'nullable|array',
            'special_actions' => 'nullable|array',
            'is_active' => 'boolean',
            'connections' => 'nullable|array',
            'connections.*.target_location_id' => 'required_with:connections|string|exists:routes,id',
            'connections.*.connection_type' => 'required_with:connections|in:start,end,bidirectional',
            'connections.*.position' => 'nullable|integer|min:0',
            'connections.*.direction' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();
            
            $town = Route::create([
                'id' => $validated['id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => 'town',
                'services' => $validated['services'] ?? [],
                'special_actions' => $validated['special_actions'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // 接続データの処理
            if (!empty($validated['connections'])) {
                $this->createConnections($town->id, $validated['connections']);
            }

            DB::commit();

            $this->auditLog('towns.created', [
                'town_id' => $town->id,
                'town_name' => $town->name,
                'connections_count' => count($validated['connections'] ?? [])
            ]);

            return redirect()->route('admin.towns.show', $town->id)
                           ->with('success', '町「' . $town->name . '」を作成しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create town', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withInput()->with('error', '町の作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('towns.show', ['town_id' => $id]);

        try {
            $town = Route::where('category', 'town')->findOrFail($id);
            
            $this->auditLog('towns.viewed', ['town_id' => $id]);

            return view('admin.towns.show', compact('town'));

        } catch (\Exception $e) {
            Log::error('Failed to load town details', [
                'town_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.towns.index')
                           ->with('error', '町の詳細の取得に失敗しました。');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        $this->trackPageAccess('towns.edit', ['town_id' => $id]);

        try {
            $town = Route::where('category', 'town')->findOrFail($id);

            return view('admin.towns.edit', compact('town'));

        } catch (\Exception $e) {
            Log::error('Failed to load town for editing', [
                'town_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.towns.index')
                           ->with('error', '町の編集画面の取得に失敗しました。');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'services' => 'nullable|array',
            'special_actions' => 'nullable|array',
            'is_active' => 'boolean',
            'connections' => 'nullable|array',
            'connections.*.target_location_id' => 'required_with:connections|string|exists:routes,id',
            'connections.*.connection_type' => 'required_with:connections|in:start,end,bidirectional',
            'connections.*.position' => 'nullable|integer|min:0',
            'connections.*.direction' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();
            
            $town = Route::where('category', 'town')->findOrFail($id);
            
            $oldData = $town->toArray();
            
            $town->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'services' => $validated['services'] ?? [],
                'special_actions' => $validated['special_actions'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // 接続データの処理（新規作成のみ - 既存は個別に管理）
            if (!empty($validated['connections'])) {
                $this->createConnections($town->id, $validated['connections']);
            }

            DB::commit();

            $this->auditLog('towns.updated', [
                'town_id' => $id,
                'old_data' => $oldData,
                'new_data' => $town->fresh()->toArray(),
                'new_connections_count' => count($validated['connections'] ?? [])
            ]);

            return redirect()->route('admin.towns.show', $id)
                           ->with('success', '町「' . $town->name . '」を更新しました。');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update town', [
                'town_id' => $id,
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()->withInput()->with('error', '町の更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');

        try {
            $town = Route::where('category', 'town')->findOrFail($id);
            $townName = $town->name;
            
            // Check for connected routes or references
            $hasConnections = $town->connections()->count() > 0 || 
                            $town->sourceConnections()->count() > 0 || 
                            $town->targetConnections()->count() > 0;
            
            if ($hasConnections) {
                return back()->with('error', '他のロケーションと接続されているため、町「' . $townName . '」を削除できません。');
            }

            $town->delete();

            $this->auditLog('towns.deleted', [
                'town_id' => $id,
                'town_name' => $townName
            ]);

            return redirect()->route('admin.towns.index')
                           ->with('success', '町「' . $townName . '」を削除しました。');

        } catch (\Exception $e) {
            Log::error('Failed to delete town', [
                'town_id' => $id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '町の削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 接続データを作成
     */
    private function createConnections(string $sourceLocationId, array $connections)
    {
        foreach ($connections as $connectionData) {
            // 重複チェック
            $existingConnection = RouteConnection::where(function($query) use ($sourceLocationId, $connectionData) {
                $query->where('source_location_id', $sourceLocationId)
                      ->where('target_location_id', $connectionData['target_location_id']);
            })->orWhere(function($query) use ($sourceLocationId, $connectionData) {
                $query->where('source_location_id', $connectionData['target_location_id'])
                      ->where('target_location_id', $sourceLocationId);
            })->first();

            if (!$existingConnection) {
                RouteConnection::create([
                    'source_location_id' => $sourceLocationId,
                    'target_location_id' => $connectionData['target_location_id'],
                    'connection_type' => $connectionData['connection_type'],
                    'position' => $connectionData['position'] ?? null,
                    'direction' => $connectionData['direction'] ?? null,
                ]);
            }
        }
    }
}
