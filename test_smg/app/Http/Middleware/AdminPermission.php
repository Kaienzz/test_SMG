<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminPermission
{
    /**
     * 管理者権限チェックミドルウェア
     * ロールベースアクセス制御（RBAC）の実装
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  必要な権限（例: 'users.edit', 'items.create'）
     * @param  string|null  $minLevel  最小権限レベル（例: 'admin', 'super'）
     */
    public function handle(Request $request, Closure $next, string $permission = null, string $minLevel = null): Response
    {
        $user = Auth::user();

        // 基本管理者権限チェック（IsAdminミドルウェアで既にチェック済みだが念のため）
        if (!$user || !$user->is_admin) {
            abort(403, '管理者権限が必要です。');
        }

        // スーパー管理者は全権限を持つ
        if ($user->admin_level === 'super' || $this->hasWildcardPermission($user)) {
            $this->logPermissionCheck($request, $user, $permission, 'granted', 'super_admin_bypass');
            return $next($request);
        }

        // 特定の権限チェック
        if ($permission && !$this->hasPermission($user, $permission)) {
            $this->logPermissionCheck($request, $user, $permission, 'denied', 'insufficient_permission');
            
            abort(403, "この操作を実行する権限がありません。必要な権限: {$permission}");
        }

        // 最小権限レベルチェック
        if ($minLevel && !$this->hasMinimumLevel($user, $minLevel)) {
            $this->logPermissionCheck($request, $user, $permission, 'denied', 'insufficient_level');
            
            abort(403, "この操作を実行する権限レベルが不足しています。必要レベル: {$minLevel}");
        }

        // 時間制限・制約チェック（将来拡張用）
        if (!$this->checkTimeConstraints($user, $permission)) {
            $this->logPermissionCheck($request, $user, $permission, 'denied', 'time_constraint');
            
            abort(403, 'この時間帯はこの操作を実行できません。');
        }

        $this->logPermissionCheck($request, $user, $permission, 'granted');

        return $next($request);
    }

    /**
     * ユーザーが特定の権限を持っているかチェック
     */
    private function hasPermission($user, string $permission): bool
    {
        // ユーザー個別権限をチェック
        $userPermissions = json_decode($user->admin_permissions ?? '[]', true);
        if (in_array($permission, $userPermissions) || in_array('*', $userPermissions)) {
            return true;
        }

        // ロール権限をチェック
        if ($user->admin_role_id) {
            $role = $this->getUserRole($user->admin_role_id);
            if ($role) {
                $rolePermissions = json_decode($role->permissions ?? '[]', true);
                
                // 完全一致チェック
                if (in_array($permission, $rolePermissions) || in_array('*', $rolePermissions)) {
                    return true;
                }

                // ワイルドカードパターンマッチング
                foreach ($rolePermissions as $rolePermission) {
                    if ($this->matchPermissionPattern($permission, $rolePermission)) {
                        return true;
                    }
                }
            }
        }

        // データベースの詳細権限をチェック
        return $this->checkDetailedPermission($user, $permission);
    }

    /**
     * ワイルドカード権限を持っているかチェック
     */
    private function hasWildcardPermission($user): bool
    {
        $userPermissions = json_decode($user->admin_permissions ?? '[]', true);
        return in_array('*', $userPermissions);
    }

    /**
     * 最小権限レベルを満たしているかチェック
     */
    private function hasMinimumLevel($user, string $minLevel): bool
    {
        $levelHierarchy = [
            'basic' => 1,
            'advanced' => 2,
            'admin' => 3,
            'super' => 4
        ];

        $userLevelValue = $levelHierarchy[$user->admin_level] ?? 0;
        $requiredLevelValue = $levelHierarchy[$minLevel] ?? 0;

        return $userLevelValue >= $requiredLevelValue;
    }

    /**
     * 権限パターンマッチング（ワイルドカード対応）
     */
    private function matchPermissionPattern(string $permission, string $pattern): bool
    {
        // ワイルドカードパターンの処理
        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);
            return str_starts_with($permission, $prefix . '.');
        }

        if (str_ends_with($pattern, '*')) {
            $prefix = substr($pattern, 0, -1);
            return str_starts_with($permission, $prefix);
        }

        return $permission === $pattern;
    }

    /**
     * ユーザーロール情報の取得（キャッシュ対応）
     */
    private function getUserRole(int $roleId)
    {
        static $roleCache = [];

        if (!isset($roleCache[$roleId])) {
            $roleCache[$roleId] = DB::table('admin_roles')
                ->where('id', $roleId)
                ->where('is_active', true)
                ->first();
        }

        return $roleCache[$roleId];
    }

    /**
     * データベースの詳細権限をチェック
     */
    private function checkDetailedPermission($user, string $permission): bool
    {
        $permissionData = DB::table('admin_permissions')
            ->where('name', $permission)
            ->where('is_active', true)
            ->first();

        if (!$permissionData) {
            return false; // 存在しない権限は拒否
        }

        // super権限ユーザーは詳細権限チェックをスキップ
        if ($user->admin_level === 'super') {
            return true;
        }

        // 権限レベルチェック
        $userRole = $this->getUserRole($user->admin_role_id);
        if ($userRole && $userRole->level < $permissionData->required_level) {
            return false;
        }

        // admin_role_idがnullの場合は基本的な権限レベルチェック
        if (!$user->admin_role_id) {
            // admin_levelが設定されている場合は最低限の権限があるとみなす
            if ($user->admin_level && $permissionData->required_level <= 1) {
                return true;
            }
        }

        // リソース制約チェック（将来拡張用）
        if ($permissionData->resource_constraints) {
            return $this->checkResourceConstraints($user, $permissionData->resource_constraints);
        }

        return true;
    }

    /**
     * 時間制約チェック（将来拡張用）
     */
    private function checkTimeConstraints($user, string $permission): bool
    {
        // 基本的には常にtrueを返す（将来の拡張ポイント）
        // 例: 特定の時間帯のみ許可、平日のみ許可など
        return true;
    }

    /**
     * リソース制約チェック（将来拡張用）
     */
    private function checkResourceConstraints($user, $constraints): bool
    {
        // 基本的には常にtrueを返す（将来の拡張ポイント）
        // 例: 特定のリソースIDのみ操作可能など
        return true;
    }

    /**
     * 権限チェックログの記録
     */
    private function logPermissionCheck(Request $request, $user, ?string $permission, string $result, string $reason = ''): void
    {
        try {
            DB::table('admin_audit_logs')->insert([
                'admin_user_id' => $user->id,
                'admin_email' => $user->email,
                'admin_name' => $user->name,
                'action' => 'permission.check',
                'action_category' => 'permission',
                'description' => "権限チェック: {$permission} - {$result}" . ($reason ? " ({$reason})" : ''),
                'resource_type' => 'Permission',
                'resource_data' => json_encode([
                    'permission' => $permission,
                    'result' => $result,
                    'reason' => $reason,
                    'user_level' => $user->admin_level,
                    'role_id' => $user->admin_role_id,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'status' => $result === 'granted' ? 'success' : 'failed',
                'severity' => $result === 'denied' ? 'medium' : 'low',
                'is_security_event' => $result === 'denied',
                'event_uuid' => \Str::uuid(),
                'event_time' => Carbon::now(),
                'tags' => json_encode(['permission', 'access_control', $result]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error('権限チェックログ記録失敗', [
                'permission' => $permission,
                'result' => $result,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
