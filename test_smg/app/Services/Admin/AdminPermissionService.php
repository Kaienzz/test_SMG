<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 管理者権限管理サービス
 * 拡張性とパフォーマンスを考慮した権限管理システム
 */
class AdminPermissionService
{
    /**
     * ユーザーが特定の権限を持っているかチェック
     */
    public function hasPermission($user, string $permission): bool
    {
        // スーパー管理者は全権限を持つ
        if ($user->admin_level === 'super' || $this->hasWildcardPermission($user)) {
            return true;
        }

        // キャッシュから権限情報を取得
        $userPermissions = $this->getUserPermissions($user);
        
        return $this->checkPermissionInList($permission, $userPermissions);
    }

    /**
     * ユーザーの全権限を取得（キャッシュ対応）
     */
    public function getUserPermissions($user): array
    {
        $cacheKey = "admin_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($user) {
            $permissions = [];

            // ユーザー個別権限
            $userPermissions = $user->admin_permissions ?? [];
            // admin_permissionsは既にUserモデルでarray型にキャストされている
            if (is_string($userPermissions)) {
                $userPermissions = json_decode($userPermissions, true) ?? [];
            }
            $permissions = array_merge($permissions, $userPermissions);

            // ロール権限
            if ($user->admin_role_id) {
                $rolePermissions = $this->getRolePermissions($user->admin_role_id);
                $permissions = array_merge($permissions, $rolePermissions);
            }

            return array_unique($permissions);
        });
    }

    /**
     * ロール権限の取得
     */
    public function getRolePermissions(int $roleId): array
    {
        $role = DB::table('admin_roles')
            ->where('id', $roleId)
            ->where('is_active', true)
            ->first();

        if (!$role) {
            return [];
        }

        return json_decode($role->permissions ?? '[]', true);
    }

    /**
     * 権限リスト内でのチェック（ワイルドカード対応）
     */
    private function checkPermissionInList(string $permission, array $permissions): bool
    {
        // 完全一致チェック
        if (in_array($permission, $permissions) || in_array('*', $permissions)) {
            return true;
        }

        // ワイルドカードパターンマッチング
        foreach ($permissions as $userPermission) {
            if ($this->matchPermissionPattern($permission, $userPermission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 権限パターンマッチング
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
     * ワイルドカード権限チェック
     */
    private function hasWildcardPermission($user): bool
    {
        $userPermissions = $user->admin_permissions ?? [];
        return in_array('*', $userPermissions);
    }

    /**
     * ユーザーに権限を付与
     */
    public function grantPermission($user, string $permission): bool
    {
        $currentPermissions = $user->admin_permissions ?? [];
        
        if (!in_array($permission, $currentPermissions)) {
            $currentPermissions[] = $permission;
            
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'admin_permissions' => json_encode($currentPermissions),
                    'admin_permissions_updated_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            // キャッシュクリア
            Cache::forget("admin_permissions_{$user->id}");
            
            return true;
        }

        return false;
    }

    /**
     * ユーザーから権限を剥奪
     */
    public function revokePermission($user, string $permission): bool
    {
        $currentPermissions = $user->admin_permissions ?? [];
        $key = array_search($permission, $currentPermissions);
        
        if ($key !== false) {
            unset($currentPermissions[$key]);
            
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'admin_permissions' => json_encode(array_values($currentPermissions)),
                    'admin_permissions_updated_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

            // キャッシュクリア
            Cache::forget("admin_permissions_{$user->id}");
            
            return true;
        }

        return false;
    }

    /**
     * 全権限一覧の取得（管理画面用）
     */
    public function getAllPermissions(): array
    {
        return Cache::remember('all_admin_permissions', now()->addHours(24), function () {
            return DB::table('admin_permissions')
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('action')
                ->get()
                ->groupBy('category')
                ->toArray();
        });
    }

    /**
     * 全ロール一覧の取得
     */
    public function getAllRoles(): array
    {
        return Cache::remember('all_admin_roles', now()->addHours(24), function () {
            return DB::table('admin_roles')
                ->where('is_active', true)
                ->orderBy('level', 'desc')
                ->get()
                ->toArray();
        });
    }

    /**
     * 権限キャッシュのクリア
     */
    public function clearPermissionCache($userId = null): void
    {
        if ($userId) {
            Cache::forget("admin_permissions_{$userId}");
        } else {
            // 全ユーザーの権限キャッシュをクリア（大規模サイトでは注意）
            Cache::flush();
        }
    }

    /**
     * 新しい権限の作成
     */
    public function createPermission(array $data): int
    {
        $permissionId = DB::table('admin_permissions')->insertGetId([
            'name' => $data['name'],
            'category' => $data['category'],
            'action' => $data['action'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'required_level' => $data['required_level'] ?? 1,
            'is_dangerous' => $data['is_dangerous'] ?? false,
            'group_name' => $data['group_name'] ?? $data['category'],
            'is_system_permission' => false,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // キャッシュクリア
        Cache::forget('all_admin_permissions');

        return $permissionId;
    }

    /**
     * 新しいロールの作成
     */
    public function createRole(array $data): int
    {
        $roleId = DB::table('admin_roles')->insertGetId([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? null,
            'level' => $data['level'],
            'permissions' => json_encode($data['permissions'] ?? []),
            'can_access_analytics' => $data['can_access_analytics'] ?? false,
            'can_manage_users' => $data['can_manage_users'] ?? false,
            'can_manage_game_data' => $data['can_manage_game_data'] ?? false,
            'can_manage_system' => $data['can_manage_system'] ?? false,
            'can_invite_admins' => $data['can_invite_admins'] ?? false,
            'is_system_role' => false,
            'is_active' => true,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // キャッシュクリア
        Cache::forget('all_admin_roles');

        return $roleId;
    }

    /**
     * 権限レベルチェック
     */
    public function hasMinimumLevel($user, int $requiredLevel): bool
    {
        if ($user->admin_level === 'super') {
            return true;
        }

        $userRole = DB::table('admin_roles')
            ->where('id', $user->admin_role_id)
            ->first();

        return $userRole && $userRole->level >= $requiredLevel;
    }

    /**
     * 危険な操作の権限チェック
     */
    public function canPerformDangerousAction($user, string $permission): bool
    {
        if (!$this->hasPermission($user, $permission)) {
            return false;
        }

        $permissionData = DB::table('admin_permissions')
            ->where('name', $permission)
            ->where('is_dangerous', true)
            ->first();

        if ($permissionData) {
            // 危険な操作には追加の確認が必要
            return $this->hasMinimumLevel($user, $permissionData->required_level);
        }

        return true;
    }
}