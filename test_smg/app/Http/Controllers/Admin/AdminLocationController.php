<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminLocationService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ロケーション管理コントローラー（統合版）
 * 
 * ロケーション管理のダッシュボードと汎用機能を提供
 * 個別のCRUD操作は専用コントローラーに分離済み：
 * - AdminTownController: 町管理
 * - AdminRoadController: 道路管理
 * - AdminDungeonController: ダンジョン管理
 * - AdminRouteConnectionController: 接続関係管理
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
     * ロケーション管理ダッシュボード
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
     * ロケーション詳細表示（汎用）
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
                return redirect()->route('admin.locations.index')
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
            
            return redirect()->route('admin.locations.index')
                           ->with('error', 'ロケーション詳細の読み込みに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * スポーンリスト管理（暫定）
     * 
     * Note: 将来的にはAdminMonsterSpawnControllerに統合予定
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
}