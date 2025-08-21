<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\Admin\AdminPermissionService;
use App\Services\Admin\AdminAuditService;

/**
 * 管理者基底コントローラー
 * 全ての管理者機能の共通基盤
 */
abstract class AdminController extends Controller
{
    protected AdminPermissionService $permissionService;
    protected AdminAuditService $auditService;
    protected $user;

    public function __construct(AdminAuditService $auditService)
    {
        // サービス初期化
        $this->permissionService = app(AdminPermissionService::class);
        $this->auditService = $auditService;
    }

    /**
     * リクエスト処理前の初期化
     */
    protected function initializeForRequest()
    {
        $this->user = Auth::user();
        $this->initializeView();
    }

    /**
     * ビューの初期化
     */
    protected function initializeView(): void
    {
        if ($this->user) {
            // Calculate permission values
            $canAccessAnalytics = $this->hasPermission('analytics.view');
            $canManageUsers = $this->hasPermission('users.view');
            $canManageItems = $this->hasPermission('items.view');
            $canManageMonsters = $this->hasPermission('monsters.view');
            $canManageShops = $this->hasPermission('shops.view');
            $canManageLocations = $this->hasPermission('locations.view');
            $canManageGameData = $canManageItems || $canManageMonsters || $canManageShops || $canManageLocations;
            $canManageSystem = $this->hasPermission('system.view') || $this->hasPermission('admin.roles') || $this->user->admin_level === 'super';


            View::share([
                'adminUser' => $this->user,
                'userPermissions' => $this->permissionService->getUserPermissions($this->user),
                'canAccessAnalytics' => $canAccessAnalytics,
                'canManageUsers' => $canManageUsers,
                'canManageItems' => $canManageItems,
                'canManageMonsters' => $canManageMonsters,
                'canManageShops' => $canManageShops,
                'canManageLocations' => $canManageLocations,
                'canManageGameData' => $canManageGameData,
                'canManageSystem' => $canManageSystem,
            ]);
        }
    }

    /**
     * 権限チェック
     */
    protected function hasPermission(string $permission): bool
    {
        if (!$this->user) {
            return false;
        }
        
        return $this->permissionService->hasPermission($this->user, $permission);
    }

    /**
     * 権限チェック（例外throw）
     */
    protected function checkPermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            abort(403, 'この機能にアクセスする権限がありません。');
        }
    }

    /**
     * 権限チェック（例外throw） - requirePermissionのエイリアス
     */
    protected function requirePermission(string $permission): void
    {
        $this->checkPermission($permission);
    }

    /**
     * 監査ログの記録
     */
    protected function auditLog(string $action, array $details = [], string $severity = 'low'): void
    {
        try {
            $description = $action . ' - ' . implode(', ', array_keys($details));
            $options = [
                'severity' => $severity,
                'resource_data' => $details,
                'category' => $this->extractCategoryFromAction($action)
            ];

            $this->auditService->logAction($action, $description, $options);
        } catch (\Exception $e) {
            // ログ記録に失敗してもメイン処理は続行
            \Log::error('Failed to log admin action: ' . $e->getMessage());
        }
    }

    /**
     * アクションからカテゴリを抽出
     */
    private function extractCategoryFromAction(string $action): string
    {
        if (str_contains($action, 'users.')) return 'users';
        if (str_contains($action, 'items.')) return 'items';  
        if (str_contains($action, 'monsters.')) return 'monsters';
        if (str_contains($action, 'shops.')) return 'shops';
        if (str_contains($action, 'locations.')) return 'locations';
        if (str_contains($action, 'page.')) return 'navigation';
        
        return 'general';
    }

    /**
     * 成功レスポンス
     */
    protected function successResponse(string $message, array $data = [])
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * エラーレスポンス
     */
    protected function errorResponse(string $message, array $errors = [], int $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * CSVエクスポート用ヘルパー
     */
    protected function exportToCsv(array $data, array $headers, string $filename): \Illuminate\Http\Response
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * ページネーション情報の取得
     */
    protected function getPaginationInfo($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * 一括操作の実行
     */
    protected function executeBulkAction(string $action, array $ids, callable $callback): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $result = $callback($id);
                if ($result) {
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = "ID {$id}: 処理に失敗しました";
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "ID {$id}: " . $e->getMessage();
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
            'total' => count($ids),
        ];
    }

    /**
     * パンくずナビの構築
     */
    protected function buildBreadcrumb(array $items): array
    {
        $breadcrumb = [
            ['title' => 'ダッシュボード', 'url' => route('admin.dashboard'), 'active' => false]
        ];

        foreach ($items as $item) {
            $breadcrumb[] = $item;
        }

        return $breadcrumb;
    }

    /**
     * ページアクセスの記録
     */
    protected function trackPageAccess(string $page): void
    {
        $this->auditLog('page.accessed', [
            'page' => $page,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}