<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\GatheringMapping;
use App\Models\Route;
use App\Models\Item;
use App\Services\Admin\AdminGatheringService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminGatheringController extends AdminController
{
    private AdminGatheringService $gatheringService;

    public function __construct(
        AdminAuditService $auditService,
        AdminGatheringService $gatheringService
    ) {
        parent::__construct($auditService);
        $this->gatheringService = $gatheringService;
    }

    /**
     * 採集管理トップページ
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.view');
        $this->trackPageAccess('gathering.index');

        try {
            // フィルタ処理
            $filters = $request->only(['route_id', 'item_category', 'skill_level', 'is_active', 'gathering_environment']);
            
            // データ取得
            $gatheringMappings = $this->gatheringService->getGatheringMappings($filters);
            $routeStats = $this->gatheringService->getGatheringStatsByRoute();
            $environmentStats = $this->gatheringService->getGatheringStatsByEnvironment();
            $routes = $this->gatheringService->getGatheringEligibleRoutes();
            $itemCategories = $this->gatheringService->getItemCategories();
            $systemSummary = $this->gatheringService->getSystemSummary();

            // 採集環境オプション
            $gatheringEnvironments = ['road', 'dungeon'];

            $this->auditLog('gathering.index.viewed', [
                'total_mappings' => $gatheringMappings->count(),
                'filters' => $filters,
                'active_mappings' => $systemSummary['active_mappings'],
            ], 'low');

            $breadcrumb = $this->buildBreadcrumb([
                ['title' => 'マップ管理', 'url' => route('admin.dashboard')],
                ['title' => '採集管理', 'active' => true]
            ]);

            return view('admin.gathering.index', compact(
                'gatheringMappings',
                'routeStats',
                'environmentStats', 
                'routes',
                'itemCategories',
                'gatheringEnvironments',
                'systemSummary',
                'filters',
                'breadcrumb'
            ));

        } catch (\Exception $e) {
            $this->auditLog('gathering.index.failed', [
                'error' => $e->getMessage()
            ], 'high');
            
            return view('admin.gathering.index', [
                'error' => '採集データの読み込みに失敗しました: ' . $e->getMessage(),
                'gatheringMappings' => collect(),
                'routeStats' => [],
                'environmentStats' => [],
                'routes' => collect(),
                'itemCategories' => collect(),
                'gatheringEnvironments' => ['road', 'dungeon'],
                'systemSummary' => ['total_mappings' => 0, 'active_mappings' => 0],
                'filters' => [],
                'breadcrumb' => $this->buildBreadcrumb([
                    ['title' => 'マップ管理', 'url' => route('admin.dashboard')],
                    ['title' => '採集管理', 'active' => true]
                ])
            ]);
        }
    }

    /**
     * 採集マッピング作成フォーム
     */
    public function create(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.create');

        $routes = $this->gatheringService->getGatheringEligibleRoutes();
        $items = Item::orderBy('name')->take(100)->get(); // 大量のアイテムを避けるため制限
        
        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'マップ管理', 'url' => route('admin.dashboard')],
            ['title' => '採集管理', 'url' => route('admin.gathering.index')],
            ['title' => '新規作成', 'active' => true]
        ]);

        return view('admin.gathering.create', compact(
            'routes',
            'items',
            'breadcrumb'
        ));
    }

    /**
     * 採集マッピング作成処理
     */
    public function store(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.create');

        $validated = $request->validate([
            'route_id' => ['required', 'string', Rule::exists('routes', 'id')],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'required_skill_level' => ['required', 'integer', 'min:1', 'max:100'],
            'success_rate' => ['required', 'integer', 'min:1', 'max:100'],
            'quantity_min' => ['required', 'integer', 'min:1'],
            'quantity_max' => ['required', 'integer', 'min:1', 'gte:quantity_min'],
            'is_active' => ['boolean'],
        ]);

        // is_activeのデフォルト設定
        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            $mapping = $this->gatheringService->createGatheringMapping($validated);

            $this->auditLog('gathering.mapping.created', [
                'mapping_id' => $mapping->id,
                'route_id' => $mapping->route_id,
                'item_id' => $mapping->item_id,
                'data' => $validated,
            ], 'medium');

            return redirect()->route('admin.gathering.index')
                           ->with('success', '採集マッピングを作成しました。');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()
                        ->withErrors(['validation' => $e->getMessage()])
                        ->with('error', '入力データに問題があります: ' . $e->getMessage());

        } catch (\Exception $e) {
            $this->auditLog('gathering.mapping.create_failed', [
                'data' => $validated,
                'error' => $e->getMessage(),
            ], 'high');

            return back()->withInput()
                        ->with('error', '採集マッピングの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 採集マッピング編集フォーム
     */
    public function edit(Request $request, GatheringMapping $mapping)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.edit');

        $mapping->load(['route', 'item']);
        $routes = $this->gatheringService->getGatheringEligibleRoutes();
        $items = Item::orderBy('name')->take(100)->get();
        
        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'マップ管理', 'url' => route('admin.dashboard')],
            ['title' => '採集管理', 'url' => route('admin.gathering.index')],
            ['title' => '編集', 'active' => true]
        ]);

        return view('admin.gathering.edit', compact(
            'mapping',
            'routes',
            'items',
            'breadcrumb'
        ));
    }

    /**
     * 採集マッピング更新処理
     */
    public function update(Request $request, GatheringMapping $mapping)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.edit');

        $validated = $request->validate([
            'route_id' => ['required', 'string', Rule::exists('routes', 'id')],
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'required_skill_level' => ['required', 'integer', 'min:1', 'max:100'],
            'success_rate' => ['required', 'integer', 'min:1', 'max:100'],
            'quantity_min' => ['required', 'integer', 'min:1'],
            'quantity_max' => ['required', 'integer', 'min:1', 'gte:quantity_min'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? false;

        try {
            $oldData = $mapping->toArray();
            $updatedMapping = $this->gatheringService->updateGatheringMapping($mapping, $validated);

            $this->auditLog('gathering.mapping.updated', [
                'mapping_id' => $mapping->id,
                'old_data' => $oldData,
                'new_data' => $validated,
                'changes' => array_diff_assoc($validated, $oldData),
            ], 'medium');

            return redirect()->route('admin.gathering.index')
                           ->with('success', '採集マッピングを更新しました。');

        } catch (\InvalidArgumentException $e) {
            return back()->withInput()
                        ->withErrors(['validation' => $e->getMessage()])
                        ->with('error', '入力データに問題があります: ' . $e->getMessage());

        } catch (\Exception $e) {
            $this->auditLog('gathering.mapping.update_failed', [
                'mapping_id' => $mapping->id,
                'data' => $validated,
                'error' => $e->getMessage(),
            ], 'high');

            return back()->withInput()
                        ->with('error', '採集マッピングの更新に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 採集マッピング削除
     */
    public function destroy(GatheringMapping $mapping)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.delete');

        try {
            $mappingData = $mapping->toArray();
            $this->gatheringService->deleteGatheringMapping($mapping);

            $this->auditLog('gathering.mapping.deleted', [
                'mapping_id' => $mapping->id,
                'deleted_data' => $mappingData,
            ], 'high');

            return redirect()->route('admin.gathering.index')
                           ->with('success', '採集マッピングを削除しました。');

        } catch (\Exception $e) {
            $this->auditLog('gathering.mapping.delete_failed', [
                'mapping_id' => $mapping->id,
                'error' => $e->getMessage(),
            ], 'high');

            return back()->with('error', '採集マッピングの削除に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 採集マッピング有効/無効切り替え
     */
    public function toggle(GatheringMapping $mapping)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.edit');

        try {
            $oldStatus = $mapping->is_active;
            $updatedMapping = $this->gatheringService->toggleGatheringMapping($mapping);

            $this->auditLog('gathering.mapping.toggled', [
                'mapping_id' => $mapping->id,
                'old_status' => $oldStatus,
                'new_status' => $updatedMapping->is_active,
            ], 'low');

            $status = $updatedMapping->is_active ? 'アクティブ' : '非アクティブ';
            return response()->json([
                'success' => true,
                'message' => "採集マッピングを{$status}に変更しました。",
                'is_active' => $updatedMapping->is_active,
            ]);

        } catch (\Exception $e) {
            $this->auditLog('gathering.mapping.toggle_failed', [
                'mapping_id' => $mapping->id,
                'error' => $e->getMessage(),
            ], 'high');

            return response()->json([
                'success' => false,
                'message' => 'ステータス変更に失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 採集統計詳細表示
     */
    public function stats(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.view');
        $this->trackPageAccess('gathering.stats');

        try {
            $systemSummary = $this->gatheringService->getSystemSummary();
            $environmentStats = $this->gatheringService->getGatheringStatsByEnvironment();
            $configurationIssues = $this->gatheringService->validateSystemConfiguration();

            $this->auditLog('gathering.stats.viewed', [
                'system_summary' => $systemSummary,
                'configuration_issues_count' => count($configurationIssues),
            ], 'low');

            $breadcrumb = $this->buildBreadcrumb([
                ['title' => 'マップ管理', 'url' => route('admin.dashboard')],
                ['title' => '採集管理', 'url' => route('admin.gathering.index')],
                ['title' => '統計', 'active' => true]
            ]);

            return view('admin.gathering.stats', compact(
                'systemSummary',
                'environmentStats',
                'configurationIssues',
                'breadcrumb'
            ));

        } catch (\Exception $e) {
            $this->auditLog('gathering.stats.failed', [
                'error' => $e->getMessage()
            ], 'high');

            return back()->with('error', '統計データの読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 既存GatheringTableからのデータ移行
     */
    public function migrateFromLegacy(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.create');

        try {
            $result = $this->gatheringService->migrateFromGatheringTable();

            $this->auditLog('gathering.legacy_migration', [
                'migrated_count' => $result['migrated_count'],
                'errors_count' => count($result['errors']),
                'errors' => $result['errors'],
            ], 'high');

            if ($result['migrated_count'] > 0) {
                $message = "{$result['migrated_count']}件のデータを移行しました。";
                if (!empty($result['errors'])) {
                    $message .= ' エラー: ' . count($result['errors']) . '件';
                }
                return redirect()->route('admin.gathering.index')
                               ->with('success', $message);
            } else {
                return redirect()->route('admin.gathering.index')
                               ->with('warning', 'データ移行できませんでした。エラー: ' . implode(', ', $result['errors']));
            }

        } catch (\Exception $e) {
            $this->auditLog('gathering.legacy_migration_failed', [
                'error' => $e->getMessage(),
            ], 'high');

            return back()->with('error', 'データ移行に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * ルート用採集設定表示（Ajax）
     */
    public function getRouteGatheringData(Request $request, string $routeId)
    {
        $this->initializeForRequest();
        $this->checkPermission('gathering.view');

        try {
            $route = Route::with('allGatheringMappings.item')->find($routeId);
            
            if (!$route) {
                return response()->json(['error' => 'ルートが見つかりません'], 404);
            }

            $gatheringStats = $route->getGatheringStats();
            $configurationIssues = $route->validateGatheringConfiguration();

            return response()->json([
                'route' => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'category' => $route->category,
                ],
                'stats' => $gatheringStats,
                'issues' => $configurationIssues,
                'mappings' => $route->allGatheringMappings->map(function($mapping) {
                    return $mapping->getGatheringInfo();
                })->toArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}