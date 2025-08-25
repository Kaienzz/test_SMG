<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminRouteService;
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
    private AdminRouteService $adminRouteService;

    public function __construct(AdminAuditService $auditService, AdminRouteService $adminRouteService)
    {
        parent::__construct($auditService);
        $this->adminRouteService = $adminRouteService;
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
                'stats' => $this->adminRouteService->getCachedStatistics(),
                'recent_backups' => $this->adminRouteService->getRecentBackups(),
                'config_status' => $this->adminRouteService->getConfigStatus()
            ];

            $this->auditLog('routes.index.viewed', [
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
            
            $location = $this->adminRouteService->getRouteDetail($locationId);

            if (!$location) {
                Log::warning('Location not found', ['location_id' => $locationId]);
                return redirect()->route('admin.locations.index')
                               ->with('error', 'ロケーションが見つかりませんでした。ID: ' . $locationId);
            }

            $this->auditLog('routes.show.viewed', [
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

}