<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\AnalyticsController;
use App\Services\Admin\AdminAuditService;
use Carbon\Carbon;

/**
 * 管理者ダッシュボードコントローラー
 * ゲーム分析データと管理者統計の統合表示
 */
class DashboardController extends AdminController
{
    private AnalyticsController $analyticsController;

    public function __construct(AdminAuditService $auditService)
    {
        parent::__construct($auditService);
        $this->analyticsController = app(AnalyticsController::class);
    }

    /**
     * ダッシュボードメイン画面
     */
    public function index(Request $request)
    {
        // リクエスト初期化
        $this->initializeForRequest();
        
        // 権限チェック
        $this->checkPermission('dashboard.view');
        
        // ページアクセス記録
        $this->trackPageAccess('dashboard');
        
        // 階層化キャッシュでダッシュボードデータを取得
        $dashboardData = $this->getOptimizedDashboardData();

        $breadcrumb = $this->buildBreadcrumb([
            ['title' => '概要', 'active' => true]
        ]);

        return view('admin.dashboard.index', [
            'dashboardData' => $dashboardData,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * リアルタイム統計API
     */
    public function realTimeStats(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('analytics.view');

        $stats = [
            'online_users' => $this->getOnlineUsersCount(),
            'active_battles' => $this->getActiveBattlesCount(),
            'recent_registrations' => $this->getRecentRegistrationsCount(),
            'system_status' => $this->getSystemStatus(),
            'last_updated' => now()->toISOString(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * 詳細分析データの取得
     */
    public function detailedAnalytics(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('analytics.advanced');

        $request->validate([
            'period' => 'sometimes|in:7d,30d,90d,1y',
            'metrics' => 'sometimes|array',
        ]);

        $period = $request->get('period', '30d');
        $requestedMetrics = $request->get('metrics', ['all']);

        $analytics = [
            'period' => $period,
            'generated_at' => now()->toISOString(),
        ];

        if (in_array('all', $requestedMetrics) || in_array('users', $requestedMetrics)) {
            $analytics['user_analytics'] = $this->getUserAnalytics($period);
        }

        if (in_array('all', $requestedMetrics) || in_array('game', $requestedMetrics)) {
            $analytics['game_analytics'] = $this->getGameAnalytics($period);
        }

        if (in_array('all', $requestedMetrics) || in_array('system', $requestedMetrics)) {
            $analytics['system_analytics'] = $this->getSystemAnalytics($period);
        }

        $this->logAction(
            'analytics.detailed_view',
            "詳細分析データ取得: {$period}",
            [
                'category' => 'analytics',
                'resource_data' => [
                    'period' => $period,
                    'metrics' => $requestedMetrics,
                ],
            ]
        );

        return $this->successResponse($analytics);
    }

    /**
     * 最適化されたダッシュボードデータ取得（階層化キャッシュ）
     */
    private function getOptimizedDashboardData(): array
    {
        return [
            // 基本統計（30分キャッシュ - 安定データ）
            'overview' => Cache::remember('dashboard_overview', now()->addMinutes(30), function () {
                return $this->getOverviewStats();
            }),
            
            // ユーザー統計（30分キャッシュ - 安定データ）
            'users' => Cache::remember('dashboard_users', now()->addMinutes(30), function () {
                return $this->getUserStats();
            }),
            
            // ゲーム統計（15分キャッシュ - 中程度の変化）
            'game' => Cache::remember('dashboard_game', now()->addMinutes(15), function () {
                return $this->getGameStats();
            }),
            
            // システム統計（20分キャッシュ - 中程度の変化）
            'system' => Cache::remember('dashboard_system', now()->addMinutes(20), function () {
                return $this->getSystemStats();
            }),
            
            // 最近のアクティビティ（5分キャッシュ - 頻繁な更新）
            'recent_activity' => Cache::remember('dashboard_activity', now()->addMinutes(5), function () {
                return $this->getRecentActivity();
            }),
            
            // アラート・通知（1分キャッシュ - リアルタイム性重視）
            'alerts' => Cache::remember('dashboard_alerts', now()->addMinutes(1), function () {
                return $this->getSystemAlerts();
            }),
            
            // パフォーマンス指標（10分キャッシュ - 適度な更新）
            'performance' => Cache::remember('dashboard_performance', now()->addMinutes(10), function () {
                return $this->getPerformanceMetrics();
            }),
        ];
    }

    /**
     * ダッシュボードキャッシュをクリア
     */
    public function clearDashboardCache(): void
    {
        $cacheKeys = [
            'dashboard_overview',
            'dashboard_users',
            'dashboard_game',
            'dashboard_system',
            'dashboard_activity',
            'dashboard_alerts',
            'dashboard_performance'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * ダッシュボードデータのコンパイル（キャッシュなし版）
     */
    private function compileDashboardData(): array
    {
        return [
            // 基本統計
            'overview' => $this->getOverviewStats(),
            
            // ユーザー統計
            'users' => $this->getUserStats(),
            
            // ゲーム統計
            'game' => $this->getGameStats(),
            
            // システム統計
            'system' => $this->getSystemStats(),
            
            // 最近のアクティビティ
            'recent_activity' => $this->getRecentActivity(),
            
            // アラート・通知
            'alerts' => $this->getSystemAlerts(),
            
            // パフォーマンス指標
            'performance' => $this->getPerformanceMetrics(),
        ];
    }

    /**
     * 概要統計の取得（クエリ最適化版）
     */
    private function getOverviewStats(): array
    {
        // ユーザー関連統計を単一クエリで取得
        $userStats = DB::table('users')
            ->selectRaw('
                COUNT(*) as total_users,
                SUM(CASE WHEN last_active_at >= ? THEN 1 ELSE 0 END) as active_users_today,
                SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admin_users
            ')
            ->addBinding(Carbon::today())
            ->first();

        // その他の統計を並行取得（異なるテーブルのため）
        $otherStats = [
            'total_players' => DB::table('players')->count(),
            'active_battles' => DB::table('active_battles')->count(),
            'total_items' => DB::table('items')->count(),
        ];

        return array_merge([
            'total_users' => $userStats->total_users,
            'active_users_today' => $userStats->active_users_today,
            'admin_users' => $userStats->admin_users,
        ], $otherStats);
    }

    /**
     * ユーザー統計の取得（クエリ最適化版）
     */
    private function getUserStats(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $yesterday = Carbon::yesterday();
        $today = Carbon::today();

        // 全ユーザー統計を単一クエリで取得
        $stats = DB::table('users')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as registrations_30d,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as registrations_7d,
                SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END) as registrations_yesterday,
                SUM(CASE WHEN last_active_at >= ? THEN 1 ELSE 0 END) as daily_active,
                SUM(CASE WHEN last_active_at >= ? THEN 1 ELSE 0 END) as weekly_active,
                SUM(CASE WHEN last_active_at >= ? THEN 1 ELSE 0 END) as monthly_active
            ')
            ->addBinding([$thirtyDaysAgo, $sevenDaysAgo, $yesterday, $today, $today, $sevenDaysAgo, $thirtyDaysAgo])
            ->first();

        return [
            'registrations' => [
                'total' => $stats->total,
                'last_30_days' => $stats->registrations_30d,
                'last_7_days' => $stats->registrations_7d,
                'yesterday' => $stats->registrations_yesterday,
            ],
            'activity' => [
                'daily_active_users' => $stats->daily_active,
                'weekly_active_users' => $stats->weekly_active,
                'monthly_active_users' => $stats->monthly_active,
            ],
            'retention' => $this->calculateUserRetention(),
        ];
    }

    /**
     * ゲーム統計の取得
     */
    private function getGameStats(): array
    {
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        return [
            'battles' => [
                'total' => DB::table('battle_logs')->count(),
                'today' => DB::table('battle_logs')
                    ->where('created_at', '>=', $today)
                    ->count(),
                'this_week' => DB::table('battle_logs')
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->count(),
                'active_now' => DB::table('active_battles')->count(),
            ],
            'economy' => [
                'total_gold' => DB::table('players')->sum('gold'),
                'average_gold' => DB::table('players')->avg('gold'),
                'items_created' => DB::table('custom_items')
                    ->where('created_at', '>=', $sevenDaysAgo)
                    ->count(),
            ],
            'progression' => [
                'average_level' => DB::table('players')->avg('level'),
                'max_level' => DB::table('players')->max('level'),
                'total_experience' => DB::table('players')->sum('experience'),
            ],
        ];
    }

    /**
     * システム統計の取得
     */
    private function getSystemStats(): array
    {
        $today = Carbon::today();
        
        return [
            'admin_activity' => [
                'total_actions_today' => DB::table('admin_audit_logs')
                    ->where('event_time', '>=', $today)
                    ->count(),
                'security_events_today' => DB::table('admin_audit_logs')
                    ->where('event_time', '>=', $today)
                    ->where('is_security_event', true)
                    ->count(),
                'failed_operations_today' => DB::table('admin_audit_logs')
                    ->where('event_time', '>=', $today)
                    ->where('status', 'failed')
                    ->count(),
            ],
            'database' => [
                'total_audit_logs' => DB::table('admin_audit_logs')->count(),
                'total_active_battles' => DB::table('active_battles')->count() ?? 0,
                'storage_usage' => $this->getStorageUsage(),
            ],
        ];
    }

    /**
     * 最近のアクティビティ取得
     */
    private function getRecentActivity(): array
    {
        return [
            'admin_actions' => DB::table('admin_audit_logs')
                ->select('description', 'admin_name', 'event_time', 'severity')
                ->orderBy('event_time', 'desc')
                ->limit(10)
                ->get()
                ->toArray(),
            'user_registrations' => DB::table('users')
                ->select('name', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
            'recent_battles' => DB::table('battle_logs')
                ->join('users', 'battle_logs.user_id', '=', 'users.id')
                ->select('users.name', 'battle_logs.created_at', 'battle_logs.result')
                ->orderBy('battle_logs.created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * システムアラートの取得（セキュリティ監視強化版）
     */
    private function getSystemAlerts(): array
    {
        $alerts = [];

        // 🔐 高度なセキュリティアラート
        $securityAlerts = $this->getAdvancedSecurityAlerts();
        $alerts = array_merge($alerts, $securityAlerts);
        
        // セキュリティアラート
        $securityEvents = DB::table('admin_audit_logs')
            ->where('is_security_event', true)
            ->where('requires_review', true)
            ->where('event_time', '>=', Carbon::now()->subHours(24))
            ->count();
            
        if ($securityEvents > 0) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'high',
                'message' => "{$securityEvents}件のセキュリティイベントが確認を待機中です。",
                'action_url' => route('admin.audit.index', ['filter' => 'security']),
            ];
        }

        // 失敗した操作アラート
        $failedOperations = DB::table('admin_audit_logs')
            ->where('status', 'failed')
            ->where('event_time', '>=', Carbon::now()->subHours(6))
            ->count();
            
        if ($failedOperations > 10) {
            $alerts[] = [
                'type' => 'warning',
                'level' => 'medium',
                'message' => "直近6時間で{$failedOperations}件の操作が失敗しています。",
                'action_url' => route('admin.audit.index', ['filter' => 'failed']),
            ];
        }

        // 新規登録急増アラート
        $recentRegistrations = DB::table('users')
            ->where('created_at', '>=', Carbon::now()->subHours(1))
            ->count();
            
        if ($recentRegistrations > 50) {
            $alerts[] = [
                'type' => 'info',
                'level' => 'low',
                'message' => "直近1時間で{$recentRegistrations}件の新規登録がありました。",
                'action_url' => route('admin.users.index'),
            ];
        }

        return $alerts;
    }

    /**
     * パフォーマンス指標の取得
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'response_time' => [
                'average' => 250, // ミリ秒（実際の実装では計測）
                'status' => 'good',
            ],
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'cache_hit_rate' => 85.5, // パーセント（実際の実装では計算）
        ];
    }

    /**
     * オンラインユーザー数の取得
     */
    private function getOnlineUsersCount(): int
    {
        return DB::table('users')
            ->where('last_active_at', '>=', Carbon::now()->subMinutes(15))
            ->count();
    }

    /**
     * アクティブバトル数の取得
     */
    private function getActiveBattlesCount(): int
    {
        return DB::table('active_battles')->count();
    }

    /**
     * 最近の登録数取得
     */
    private function getRecentRegistrationsCount(): int
    {
        return DB::table('users')
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();
    }

    /**
     * システムステータス取得
     */
    private function getSystemStatus(): array
    {
        return [
            'database' => 'healthy',
            'cache' => 'healthy',
            'storage' => 'healthy',
            'overall' => 'healthy',
        ];
    }

    /**
     * ユーザー保持率の計算
     */
    private function calculateUserRetention(): array
    {
        $totalUsers = DB::table('users')->count();
        $activeLastWeek = DB::table('users')
            ->where('last_active_at', '>=', Carbon::now()->subWeek())
            ->count();
        $activeLastMonth = DB::table('users')
            ->where('last_active_at', '>=', Carbon::now()->subMonth())
            ->count();

        return [
            'weekly' => $totalUsers > 0 ? round(($activeLastWeek / $totalUsers) * 100, 2) : 0,
            'monthly' => $totalUsers > 0 ? round(($activeLastMonth / $totalUsers) * 100, 2) : 0,
        ];
    }

    /**
     * ストレージ使用量の取得
     */
    private function getStorageUsage(): array
    {
        return [
            'total' => '100MB', // 実際の実装では計算
            'available' => '1.9GB',
            'percentage' => 5.0,
        ];
    }

    /**
     * 期間別ユーザー分析
     */
    private function getUserAnalytics(string $period): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);

        return [
            'new_users' => DB::table('users')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'active_users' => DB::table('users')
                ->where('last_active_at', '>=', $startDate)
                ->count(),
            'user_growth' => $this->calculateUserGrowth($startDate),
        ];
    }

    /**
     * 期間別ゲーム分析
     */
    private function getGameAnalytics(string $period): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);

        return [
            'battles_count' => DB::table('battle_logs')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'items_created' => DB::table('custom_items')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'average_session_time' => 45.5, // 分（実際の実装では計算）
        ];
    }

    /**
     * 期間別システム分析
     */
    private function getSystemAnalytics(string $period): array
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);

        return [
            'admin_actions' => DB::table('admin_audit_logs')
                ->where('event_time', '>=', $startDate)
                ->count(),
            'security_events' => DB::table('admin_audit_logs')
                ->where('event_time', '>=', $startDate)
                ->where('is_security_event', true)
                ->count(),
            'error_rate' => 2.1, // パーセント（実際の実装では計算）
        ];
    }

    /**
     * ユーザー成長率の計算
     */
    private function calculateUserGrowth(Carbon $startDate): float
    {
        $periodUsers = DB::table('users')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $previousPeriodUsers = DB::table('users')
            ->where('created_at', '<', $startDate)
            ->where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(Carbon::now())))
            ->count();

        if ($previousPeriodUsers == 0) {
            return $periodUsers > 0 ? 100.0 : 0.0;
        }

        return round((($periodUsers - $previousPeriodUsers) / $previousPeriodUsers) * 100, 2);
    }

    /**
     * 高度なセキュリティアラート検出
     */
    private function getAdvancedSecurityAlerts(): array
    {
        $alerts = [];
        $now = Carbon::now();

        // 1. 異常な権限使用パターンの検出
        $suspiciousPermissionUsage = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subHours(24))
            ->where('admin_name', '!=', null)
            ->whereIn('description', ['Permission granted', 'Permission denied', 'Role changed'])
            ->groupBy('admin_name')
            ->havingRaw('COUNT(*) > 100')
            ->count();

        if ($suspiciousPermissionUsage > 0) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'critical',
                'category' => 'permission_abuse',
                'message' => "{$suspiciousPermissionUsage}人の管理者で異常な権限使用パターンが検出されました（24時間で100回以上）",
                'action_url' => route('admin.audit.index', ['filter' => 'permissions']),
                'detected_at' => $now->toISOString(),
            ];
        }

        // 2. 短時間内の大量失敗操作検出
        $recentFailures = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subMinutes(30))
            ->where('status', 'failed')
            ->count();

        if ($recentFailures > 20) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'high',
                'category' => 'failed_operations',
                'message' => "直近30分で{$recentFailures}件の操作が失敗しています（可能性のあるブルートフォース攻撃）",
                'action_url' => route('admin.audit.index', ['filter' => 'failed']),
                'detected_at' => $now->toISOString(),
            ];
        }

        // 3. 疑わしいIPアドレスからのアクセス検出
        $suspiciousIPs = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subHours(6))
            ->where('status', 'failed')
            ->groupBy('ip_address')
            ->havingRaw('COUNT(*) > 10')
            ->pluck('ip_address');

        if ($suspiciousIPs->count() > 0) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'high',
                'category' => 'suspicious_ip',
                'message' => $suspiciousIPs->count() . "個のIPアドレスから大量の失敗操作が検出されています",
                'action_url' => route('admin.audit.index', ['filter' => 'ip_analysis']),
                'detected_at' => $now->toISOString(),
                'data' => ['suspicious_ips' => $suspiciousIPs->take(5)->toArray()]
            ];
        }

        // 4. 管理者アカウントの異常ログイン検出
        $adminLoginFailures = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subHours(12))
            ->where('description', 'like', '%login%failed%')
            ->orWhere('description', 'like', '%authentication%failed%')
            ->groupBy('admin_name')
            ->havingRaw('COUNT(*) > 5')
            ->count();

        if ($adminLoginFailures > 0) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'critical',
                'category' => 'admin_login_attempts',
                'message' => "{$adminLoginFailures}個の管理者アカウントで異常なログイン試行が検出されました",
                'action_url' => route('admin.audit.index', ['filter' => 'login_failures']),
                'detected_at' => $now->toISOString(),
            ];
        }

        // 5. 権限昇格の検出
        $privilegeEscalation = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subHours(24))
            ->where('description', 'like', '%permission%granted%')
            ->where('severity', 'high')
            ->count();

        if ($privilegeEscalation > 0) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'high',
                'category' => 'privilege_escalation',
                'message' => "直近24時間で{$privilegeEscalation}件の権限昇格操作が実行されました",
                'action_url' => route('admin.audit.index', ['filter' => 'privilege_changes']),
                'detected_at' => $now->toISOString(),
            ];
        }

        // 6. システムファイルへの異常アクセス検出
        $systemFileAccess = DB::table('admin_audit_logs')
            ->where('event_time', '>=', $now->subHours(6))
            ->where('description', 'like', '%file%')
            ->where('description', 'like', '%system%')
            ->orWhere('description', 'like', '%config%')
            ->count();

        if ($systemFileAccess > 10) {
            $alerts[] = [
                'type' => 'security',
                'level' => 'medium',
                'category' => 'system_file_access',
                'message' => "システムファイルへの異常なアクセスが{$systemFileAccess}件検出されています",
                'action_url' => route('admin.audit.index', ['filter' => 'system_access']),
                'detected_at' => $now->toISOString(),
            ];
        }

        return $alerts;
    }
}
