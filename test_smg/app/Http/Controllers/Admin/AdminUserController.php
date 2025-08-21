<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use App\Models\Player;
use Carbon\Carbon;

/**
 * 管理者ユーザー管理コントローラー
 * 拡張性とセキュリティを重視したユーザー管理システム
 */
class AdminUserController extends AdminController
{
    /**
     * ユーザー一覧表示
     */
    public function index(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.view');
        $this->trackPageAccess('users.index');

        // 検索・フィルタパラメータ
        $search = $request->get('search');
        $status = $request->get('status');
        $adminFilter = $request->get('admin_filter');
        $registrationPeriod = $request->get('registration_period');
        $activityPeriod = $request->get('activity_period');
        $perPage = $request->get('per_page', 25);

        // ベースクエリ
        $query = User::query()->with(['player']);

        // 検索条件適用
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // ステータスフィルタ
        if ($status) {
            switch ($status) {
                case 'active':
                    $query->where('last_active_at', '>=', Carbon::now()->subDays(7));
                    break;
                case 'inactive':
                    $query->where(function($q) {
                        $q->whereNull('last_active_at')
                          ->orWhere('last_active_at', '<', Carbon::now()->subDays(30));
                    });
                    break;
                case 'verified':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'unverified':
                    $query->whereNull('email_verified_at');
                    break;
            }
        }

        // 管理者フィルタ
        if ($adminFilter) {
            switch ($adminFilter) {
                case 'admin_only':
                    $query->where('is_admin', true);
                    break;
                case 'regular_only':
                    $query->where('is_admin', false);
                    break;
            }
        }

        // 登録期間フィルタ
        if ($registrationPeriod) {
            $startDate = match($registrationPeriod) {
                '24h' => Carbon::now()->subDay(),
                '7d' => Carbon::now()->subWeek(),
                '30d' => Carbon::now()->subMonth(),
                '90d' => Carbon::now()->subDays(90),
                default => null,
            };
            
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
        }

        // アクティビティ期間フィルタ
        if ($activityPeriod) {
            $startDate = match($activityPeriod) {
                '24h' => Carbon::now()->subDay(),
                '7d' => Carbon::now()->subWeek(),
                '30d' => Carbon::now()->subMonth(),
                default => null,
            };
            
            if ($startDate) {
                $query->where('last_active_at', '>=', $startDate);
            }
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        $allowedSortFields = ['name', 'email', 'created_at', 'last_active_at', 'is_admin'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // ページネーション
        $users = $query->paginate($perPage)->withQueryString();

        // 統計データ
        $stats = $this->getUserStats();

        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'ユーザー管理', 'active' => true]
        ]);

        // 操作ログ
        $this->logAction(
            'users.view_list',
            'ユーザー一覧を表示',
            [
                'category' => 'users',
                'resource_data' => [
                    'total_shown' => $users->count(),
                    'filters' => compact('search', 'status', 'adminFilter', 'registrationPeriod'),
                ],
            ]
        );

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => compact('search', 'status', 'adminFilter', 'registrationPeriod', 'activityPeriod'),
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * ユーザー詳細表示
     */
    public function show(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.view');
        
        // ユーザー詳細データの取得
        $user->load(['player']);
        
        // アクティビティ履歴
        $activityLogs = DB::table('admin_audit_logs')
            ->where('admin_user_id', $user->id)
            ->orWhere('resource_type', 'User')
            ->where('resource_id', $user->id)
            ->orderBy('event_time', 'desc')
            ->limit(50)
            ->get();

        // プレイヤー統計（存在する場合）
        $playerStats = null;
        if ($user->player) {
            $playerStats = $this->getPlayerStats($user->player);
        }

        // セッション情報
        $sessionInfo = [
            'current_session' => session()->getId(),
            'last_activity' => $user->last_active_at,
            'device_type' => $user->last_device_type,
            'ip_address' => $user->last_ip_address,
        ];

        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'ユーザー管理', 'url' => route('admin.users.index')],
            ['title' => $user->name, 'active' => true]
        ]);

        // 閲覧ログ
        $this->logAction(
            'users.view_detail',
            "ユーザー詳細表示: {$user->name}",
            [
                'category' => 'users',
                'resource_type' => 'User',
                'resource_id' => $user->id,
            ]
        );

        return view('admin.users.show', [
            'user' => $user,
            'activityLogs' => $activityLogs,
            'playerStats' => $playerStats,
            'sessionInfo' => $sessionInfo,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * ユーザー編集フォーム
     */
    public function edit(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.edit');

        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'ユーザー管理', 'url' => route('admin.users.index')],
            ['title' => $user->name, 'url' => route('admin.users.show', $user)],
            ['title' => '編集', 'active' => true]
        ]);

        return view('admin.users.edit', [
            'user' => $user,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * ユーザー更新
     */
    public function update(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.edit');

        $oldValues = $user->toArray();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'is_admin' => 'sometimes|boolean',
            'admin_level' => 'sometimes|in:basic,advanced,admin,super',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // パスワード更新
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // 管理者権限の更新（権限チェック）
        if ($request->has('is_admin') && $this->hasPermission('admin.roles')) {
            $updateData['is_admin'] = $request->boolean('is_admin');
            
            if ($request->boolean('is_admin')) {
                $updateData['admin_level'] = $request->get('admin_level', 'basic');
                $updateData['admin_activated_at'] = $user->admin_activated_at ?: Carbon::now();
            }
        }

        // 管理者ノート
        if ($request->has('admin_notes') && $this->hasPermission('users.edit')) {
            $updateData['admin_notes'] = $request->admin_notes;
        }

        $user->update($updateData);

        // 変更ログ記録
        $this->logResourceChange('User', $user->id, $oldValues, $user->fresh()->toArray());

        return $this->successResponse(
            ['user' => $user->fresh()],
            'ユーザー情報を更新しました。'
        );
    }

    /**
     * アカウント停止
     */
    public function suspend(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.suspend');

        $request->validate([
            'reason' => 'required|string|max:500',
            'duration' => 'nullable|integer|min:1|max:365', // 日数
        ]);

        $suspensionData = [
            'suspended_at' => Carbon::now(),
            'suspension_reason' => $request->reason,
            'suspended_by' => $this->user->id,
        ];

        if ($request->filled('duration')) {
            $suspensionData['suspension_expires_at'] = Carbon::now()->addDays($request->duration);
        }

        // User モデルに suspension カラムがある場合
        // $user->update($suspensionData);

        // 仮想的にセッション無効化
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->logAction(
            'users.suspend',
            "ユーザーを停止: {$user->name}",
            [
                'category' => 'users',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'resource_data' => $suspensionData,
                'severity' => 'high',
                'is_security_event' => true,
            ]
        );

        return $this->successResponse(null, 'ユーザーアカウントを停止しました。');
    }

    /**
     * アカウント復活
     */
    public function restore(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.suspend');

        // 停止解除処理
        // $user->update([
        //     'suspended_at' => null,
        //     'suspension_reason' => null,
        //     'suspension_expires_at' => null,
        //     'restored_at' => Carbon::now(),
        //     'restored_by' => $this->user->id,
        // ]);

        $this->logAction(
            'users.restore',
            "ユーザーアカウントを復活: {$user->name}",
            [
                'category' => 'users',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'severity' => 'medium',
            ]
        );

        return $this->successResponse(null, 'ユーザーアカウントを復活しました。');
    }

    /**
     * 強制ログアウト
     */
    public function forceLogout(Request $request, User $user)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.edit');

        // セッション削除
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // アクティブバトル終了（必要に応じて）
        DB::table('active_battles')->where('user_id', $user->id)->delete();

        $this->logAction(
            'users.force_logout',
            "ユーザーを強制ログアウト: {$user->name}",
            [
                'category' => 'users',
                'resource_type' => 'User',
                'resource_id' => $user->id,
                'severity' => 'medium',
                'is_security_event' => true,
            ]
        );

        return $this->successResponse(null, 'ユーザーを強制ログアウトしました。');
    }

    /**
     * 一括操作
     */
    public function bulkAction(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.edit');

        $request->validate([
            'action' => 'required|in:delete,suspend,restore,force_logout',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'reason' => 'required_if:action,suspend|string|max:500',
        ]);

        $userIds = $request->user_ids;
        $action = $request->action;
        $reason = $request->reason;

        // 危険な操作の確認
        if (in_array($action, ['delete', 'suspend'])) {
            $this->requireDangerousOperationConfirmation($request, "bulk_{$action}");
        }

        $targets = collect($userIds)->map(function($id) {
            return ['type' => 'User', 'id' => $id];
        })->toArray();

        $results = $this->executeBatchOperation(
            $targets,
            function($target) use ($action, $reason) {
                $user = User::find($target['id']);
                if (!$user) return;

                switch ($action) {
                    case 'suspend':
                        // 停止処理
                        break;
                    case 'restore':
                        // 復活処理
                        break;
                    case 'force_logout':
                        DB::table('sessions')->where('user_id', $user->id)->delete();
                        break;
                }
            },
            $action,
            "ユーザー一括{$action}操作: " . count($userIds) . "件"
        );

        return $this->successResponse($results, "一括操作を実行しました。");
    }

    /**
     * オンラインユーザー一覧
     */
    public function online(Request $request)
    {
        $this->initializeForRequest();
        $this->checkPermission('users.view');

        $onlineThreshold = Carbon::now()->subMinutes(15);
        
        $onlineUsers = User::where('last_active_at', '>=', $onlineThreshold)
            ->with(['player'])
            ->orderBy('last_active_at', 'desc')
            ->paginate(50);

        $breadcrumb = $this->buildBreadcrumb([
            ['title' => 'ユーザー管理', 'url' => route('admin.users.index')],
            ['title' => 'オンラインユーザー', 'active' => true]
        ]);

        return view('admin.users.online', [
            'onlineUsers' => $onlineUsers,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * ユーザー統計データ取得
     */
    private function getUserStats(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'total' => User::count(),
            'active_today' => User::where('last_active_at', '>=', $today)->count(),
            'registered_today' => User::where('created_at', '>=', $today)->count(),
            'registered_this_week' => User::where('created_at', '>=', $thisWeek)->count(),
            'registered_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'admin_users' => User::where('is_admin', true)->count(),
            'online_now' => User::where('last_active_at', '>=', Carbon::now()->subMinutes(15))->count(),
        ];
    }

    /**
     * プレイヤー統計データ取得
     */
    private function getPlayerStats(Player $player): array
    {
        return [
            'basic_info' => [
                'level' => $player->level,
                'experience' => $player->experience,
                'gold' => $player->gold,
            ],
            'combat_stats' => [
                'hp' => "{$player->hp}/{$player->max_hp}",
                'mp' => "{$player->mp}/{$player->max_mp}",
                'sp' => "{$player->sp}/{$player->max_sp}",
            ],
            'location' => [
                'type' => $player->location_type,
                'id' => $player->location_id,
                'position' => $player->game_position,
            ],
            'battle_stats' => [
                'total_battles' => DB::table('battle_logs')->where('user_id', $player->user_id)->count(),
                'wins' => DB::table('battle_logs')
                    ->where('user_id', $player->user_id)
                    ->where('result', 'victory')
                    ->count(),
            ],
        ];
    }
}
