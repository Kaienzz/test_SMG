<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Services\Admin\AdminRouteService;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

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

    /**
     * ロケーションデータをJSONファイルとしてエクスポート
     */
    public function export(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        try {
            // ロケーションデータを取得
            $locations = $this->adminRouteService->getAllLocationData();
            
            // エクスポートファイル名を生成
            $filename = 'locations_export_' . date('Y-m-d_H-i-s') . '.json';
            
            // JSON形式でデータを準備
            $exportData = [
                'export_date' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'locations' => $locations
            ];

            $this->auditLog('locations.export', [
                'filename' => $filename,
                'location_count' => count($locations)
            ]);

            // JSONレスポンスとしてダウンロード
            return response()->json($exportData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export location data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('admin.locations.index')
                           ->with('error', 'エクスポートに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * アップロードされたJSONファイルからロケーションデータをインポート
     */
    public function import(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        $request->validate([
            'config_file' => 'required|file|mimes:json|max:2048'
        ]);

        try {
            $file = $request->file('config_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('無効なJSONファイルです: ' . json_last_error_msg());
            }

            // データの形式をバリデーション
            if (!isset($data['locations']) || !is_array($data['locations'])) {
                throw new \Exception('無効なロケーションデータ形式です。');
            }

            // バックアップを作成
            $backupResult = $this->adminRouteService->createBackup('before_import_' . date('Y-m-d_H-i-s'));
            
            // インポートを実行
            $result = $this->adminRouteService->importLocationData($data['locations']);

            $this->auditLog('locations.import', [
                'filename' => $file->getClientOriginalName(),
                'location_count' => count($data['locations']),
                'backup_created' => $backupResult
            ]);

            return redirect()->route('admin.locations.index')
                           ->with('success', 'ロケーションデータのインポートが完了しました。' . count($data['locations']) . '件のロケーションが処理されました。');

        } catch (\Exception $e) {
            Log::error('Failed to import location data', [
                'error' => $e->getMessage(),
                'file' => $request->file('config_file') ? $request->file('config_file')->getClientOriginalName() : 'none'
            ]);

            return redirect()->route('admin.locations.index')
                           ->with('error', 'インポートに失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * バックアップファイルからロケーションデータを復元
     */
    public function restore(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('locations.view');

        $request->validate([
            'backup_file' => 'required|string'
        ]);

        try {
            $backupFile = $request->input('backup_file');
            
            // 現在の設定をバックアップ
            $currentBackup = $this->adminRouteService->createBackup('before_restore_' . date('Y-m-d_H-i-s'));
            
            // バックアップから復元を実行
            $result = $this->adminRouteService->restoreFromBackup($backupFile);

            $this->auditLog('locations.restore', [
                'backup_file' => $backupFile,
                'current_backup_created' => $currentBackup
            ]);

            return redirect()->route('admin.locations.index')
                           ->with('success', 'バックアップ「' . $backupFile . '」からの復元が完了しました。');

        } catch (\Exception $e) {
            Log::error('Failed to restore location data', [
                'error' => $e->getMessage(),
                'backup_file' => $request->input('backup_file')
            ]);

            return redirect()->route('admin.locations.index')
                           ->with('error', '復元に失敗しました: ' . $e->getMessage());
        }
    }

}