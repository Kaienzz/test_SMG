<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminLocationService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ロケーション管理コントローラー（SQLite対応）
 * 
 * SQLiteデータベースのロケーション設定を管理画面で管理
 */
class AdminLocationController extends AdminController
{
    private AdminLocationService $adminLocationService;

    public function __construct(AdminAuditService $auditService, AdminLocationService $adminLocationService)
    {
        parent::__construct($auditService);
        $this->adminLocationService = $adminLocationService;
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

            $this->auditLog('locations.index.viewed', [
                'stats' => $data['stats']
            ]);

            return view('admin.locations.index', $data);

        } catch (\Exception $e) {
            Log::error('Failed to load location data for admin view', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.index', [
                'error' => 'ロケーションデータの読み込みに失敗しました: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 道路・ダンジョン統合管理ページ
     */
    public function pathways(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.pathways');

        $filters = $request->only(['search', 'category', 'difficulty', 'sort_by', 'sort_direction']);
        
        try {
            $pathways = $this->adminLocationService->getPathways($filters);
            $difficulties = $this->adminLocationService->getAvailableDifficulties();

            $this->auditLog('locations.pathways.viewed', [
                'filters' => $filters,
                'result_count' => count($pathways)
            ]);

            return view('admin.locations.pathways.index', compact(
                'pathways',
                'filters',
                'difficulties'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load pathways data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.pathways.index', [
                'error' => 'パスウェイデータの読み込みに失敗しました: ' . $e->getMessage(),
                'pathways' => [],
                'filters' => $filters,
                'difficulties' => []
            ]);
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

        $filters = $request->only(['search', 'sort_by', 'sort_direction']);
        
        try {
            $towns = $this->adminLocationService->getTowns($filters);

            $this->auditLog('locations.towns.viewed', [
                'filters' => $filters,
                'result_count' => count($towns)
            ]);

            return view('admin.locations.towns.index', compact(
                'towns',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load towns data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.towns.index', [
                'error' => '町データの読み込みに失敗しました: ' . $e->getMessage(),
                'towns' => [],
                'filters' => $filters
            ]);
        }
    }

    /**
     * 接続管理ページ
     */
    public function connections(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        $this->trackPageAccess('locations.connections');

        $filters = $request->only(['connection_type', 'source_location', 'sort_by', 'sort_direction']);
        
        try {
            $connections = $this->adminLocationService->getConnections($filters);
            $locations = $this->adminLocationService->getPathways() + $this->adminLocationService->getTowns();

            $this->auditLog('locations.connections.viewed', [
                'filters' => $filters,
                'result_count' => count($connections)
            ]);

            return view('admin.locations.connections.index', compact(
                'connections',
                'locations',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load connections data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.connections.index', [
                'error' => '接続データの読み込みに失敗しました: ' . $e->getMessage(),
                'connections' => [],
                'locations' => [],
                'filters' => $filters
            ]);
        }
    }

    /**
     * スポーンリスト管理ページ
     */
    public function spawnLists(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('monsters.view');
        $this->trackPageAccess('locations.spawn_lists');

        try {
            $spawnLists = $this->adminLocationService->getSpawnLists();

            $this->auditLog('locations.spawn_lists.viewed', [
                'result_count' => count($spawnLists)
            ]);

            return view('admin.locations.spawn-lists.index', compact('spawnLists'));

        } catch (\Exception $e) {
            Log::error('Failed to load spawn lists data', [
                'error' => $e->getMessage()
            ]);
            
            return view('admin.locations.spawn-lists.index', [
                'error' => 'スポーンリストデータの読み込みに失敗しました: ' . $e->getMessage(),
                'spawnLists' => []
            ]);
        }
    }

    /**
     * ロケーション詳細表示（モジュラー版）
     */
    public function show(Request $request, string $locationId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            Log::info('Location detail request', ['location_id' => $locationId]);
            
            $location = $this->adminLocationService->getLocationDetail($locationId);

            if (!$location) {
                Log::warning('Location not found', ['location_id' => $locationId]);
                return redirect()->route('admin.locations.pathways')
                               ->with('error', 'ロケーションが見つかりませんでした。ID: ' . $locationId);
            }

            $this->auditLog('locations.show.viewed', [
                'location_id' => $locationId,
                'location_name' => $location['name']
            ]);

            Log::info('Location detail loaded successfully', [
                'location_id' => $locationId,
                'location_name' => $location['name']
            ]);

            return view('admin.locations.show', compact('location'));

        } catch (\Exception $e) {
            Log::error('Failed to load location detail', [
                'location_id' => $locationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.locations.pathways')
                           ->with('error', 'ロケーション詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * パスウェイ編集フォーム表示
     */
    public function pathwayForm(Request $request, string $pathwayId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        
        // TODO: 実装予定 - パスウェイ編集フォーム
        return redirect()->route('admin.locations.pathways')
                       ->with('info', 'パスウェイ編集機能は準備中です。');
    }

    /**
     * パスウェイ詳細表示（後方互換性）
     */
    public function pathwayDetails(Request $request, string $pathwayId)
    {
        // 新しい詳細表示にリダイレクト
        return redirect()->route('admin.locations.show', $pathwayId);
    }

    // ===== レガシー互換性メソッド（新システムへのリダイレクト） =====

    /**
     * Road管理（レガシー互換性 - 新システムにリダイレクト）
     */
    public function roads(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        $this->auditLog('locations.roads.legacy_redirect', [
            'redirect_to' => 'admin.roads.index'
        ]);

        return redirect()->route('admin.roads.index')
                       ->with('info', 'Road管理機能が新しいシステムに移行されました。');
    }

    /**
     * Road作成フォーム（レガシー互換性 - 新システムにリダイレクト）
     */
    public function roadForm(Request $request, string $roadId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        
        if ($roadId) {
            $this->auditLog('locations.roads.edit_legacy_redirect', [
                'road_id' => $roadId,
                'redirect_to' => 'admin.roads.edit'
            ]);
            return redirect()->route('admin.roads.edit', $roadId)
                           ->with('info', 'Road編集機能が新しいシステムに移行されました。');
        } else {
            $this->auditLog('locations.roads.create_legacy_redirect', [
                'redirect_to' => 'admin.roads.create'
            ]);
            return redirect()->route('admin.roads.create')
                           ->with('info', 'Road作成機能が新しいシステムに移行されました。');
        }
    }

    /**
     * Road詳細表示（レガシー互換性 - 新システムにリダイレクト）
     */
    public function roadDetails(Request $request, string $roadId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        $this->auditLog('locations.roads.details_legacy_redirect', [
            'road_id' => $roadId,
            'redirect_to' => 'admin.roads.show'
        ]);

        return redirect()->route('admin.roads.show', $roadId)
                       ->with('info', 'Road詳細表示が新しいシステムに移行されました。');
    }

    /**
     * Dungeon管理（レガシー互換性 - 新システムにリダイレクト）
     */
    public function dungeons(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        $this->auditLog('locations.dungeons.legacy_redirect', [
            'redirect_to' => 'admin.dungeons.index'
        ]);

        return redirect()->route('admin.dungeons.index')
                       ->with('info', 'Dungeon管理機能が新しいシステムに移行されました。');
    }

    /**
     * Dungeon作成フォーム（レガシー互換性 - 新システムにリダイレクト）
     */
    public function dungeonForm(Request $request, string $dungeonId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        
        if ($dungeonId) {
            $this->auditLog('locations.dungeons.edit_legacy_redirect', [
                'dungeon_id' => $dungeonId,
                'redirect_to' => 'admin.dungeons.edit'
            ]);
            return redirect()->route('admin.dungeons.edit', $dungeonId)
                           ->with('info', 'Dungeon編集機能が新しいシステムに移行されました。');
        } else {
            $this->auditLog('locations.dungeons.create_legacy_redirect', [
                'redirect_to' => 'admin.dungeons.create'
            ]);
            return redirect()->route('admin.dungeons.create')
                           ->with('info', 'Dungeon作成機能が新しいシステムに移行されました。');
        }
    }

    /**
     * Dungeon詳細表示（レガシー互換性 - 新システムにリダイレクト）
     */
    public function dungeonDetails(Request $request, string $dungeonId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        $this->auditLog('locations.dungeons.details_legacy_redirect', [
            'dungeon_id' => $dungeonId,
            'redirect_to' => 'admin.dungeons.show'
        ]);

        return redirect()->route('admin.dungeons.show', $dungeonId)
                       ->with('info', 'Dungeon詳細表示が新しいシステムに移行されました。');
    }

    /**
     * 町作成フォーム（将来実装予定）
     */
    public function townForm(Request $request, string $townId = null)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.edit');
        
        // TODO: 実装予定 - 町編集フォーム
        return redirect()->route('admin.locations.towns')
                       ->with('info', '町編集機能は準備中です。');
    }

    /**
     * 町詳細表示（統合詳細表示にリダイレクト）
     */
    public function townDetails(Request $request, string $townId)
    {
        // 統合詳細表示にリダイレクト
        return redirect()->route('admin.locations.show', $townId);
    }

    /**
     * 接続詳細表示（将来実装予定）
     */
    public function connectionDetails(Request $request, string $locationId)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        // TODO: 実装予定 - 接続詳細表示
        return redirect()->route('admin.locations.connections')
                       ->with('info', '接続詳細表示機能は準備中です。');
    }

    /**
     * 接続検証（将来実装予定）
     */
    public function validateConnections(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');
        
        // TODO: 実装予定 - 接続検証機能
        return redirect()->route('admin.locations.connections')
                       ->with('info', '接続検証機能は準備中です。');
    }
}