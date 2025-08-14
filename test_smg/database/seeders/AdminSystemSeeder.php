<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 拡張性を考慮した権限システムの初期設定
        $this->createPermissions();
        $this->createRoles();
        $this->createInitialAdmin();
        
        $this->command->info('管理者システムの基本データが正常に作成されました。');
    }

    /**
     * 詳細権限の作成（将来拡張対応）
     */
    private function createPermissions(): void
    {
        $permissions = [
            // ユーザー管理権限
            ['name' => 'users.view', 'category' => 'users', 'action' => 'view', 'display_name' => 'ユーザー一覧表示', 'required_level' => 10],
            ['name' => 'users.create', 'category' => 'users', 'action' => 'create', 'display_name' => 'ユーザー作成', 'required_level' => 30],
            ['name' => 'users.edit', 'category' => 'users', 'action' => 'edit', 'display_name' => 'ユーザー編集', 'required_level' => 20],
            ['name' => 'users.delete', 'category' => 'users', 'action' => 'delete', 'display_name' => 'ユーザー削除', 'required_level' => 50, 'is_dangerous' => true],
            ['name' => 'users.suspend', 'category' => 'users', 'action' => 'suspend', 'display_name' => 'ユーザー停止', 'required_level' => 40, 'is_dangerous' => true],

            // プレイヤー管理権限
            ['name' => 'players.view', 'category' => 'players', 'action' => 'view', 'display_name' => 'プレイヤー情報表示', 'required_level' => 10],
            ['name' => 'players.edit', 'category' => 'players', 'action' => 'edit', 'display_name' => 'プレイヤー情報編集', 'required_level' => 30],
            ['name' => 'players.reset', 'category' => 'players', 'action' => 'reset', 'display_name' => 'プレイヤーリセット', 'required_level' => 40, 'is_dangerous' => true],
            ['name' => 'players.stats', 'category' => 'players', 'action' => 'stats', 'display_name' => 'ステータス調整', 'required_level' => 30],

            // アイテム・ゲームデータ管理
            ['name' => 'items.view', 'category' => 'items', 'action' => 'view', 'display_name' => 'アイテム表示', 'required_level' => 10],
            ['name' => 'items.create', 'category' => 'items', 'action' => 'create', 'display_name' => 'アイテム作成', 'required_level' => 30],
            ['name' => 'items.edit', 'category' => 'items', 'action' => 'edit', 'display_name' => 'アイテム編集', 'required_level' => 25],
            ['name' => 'items.delete', 'category' => 'items', 'action' => 'delete', 'display_name' => 'アイテム削除', 'required_level' => 40, 'is_dangerous' => true],
            
            ['name' => 'monsters.view', 'category' => 'monsters', 'action' => 'view', 'display_name' => 'モンスター表示', 'required_level' => 10],
            ['name' => 'monsters.edit', 'category' => 'monsters', 'action' => 'edit', 'display_name' => 'モンスター編集', 'required_level' => 30],
            
            ['name' => 'shops.view', 'category' => 'shops', 'action' => 'view', 'display_name' => 'ショップ表示', 'required_level' => 10],
            ['name' => 'shops.edit', 'category' => 'shops', 'action' => 'edit', 'display_name' => 'ショップ編集', 'required_level' => 25],

            // 分析・監視権限
            ['name' => 'analytics.view', 'category' => 'analytics', 'action' => 'view', 'display_name' => '分析データ表示', 'required_level' => 15],
            ['name' => 'analytics.export', 'category' => 'analytics', 'action' => 'export', 'display_name' => 'データエクスポート', 'required_level' => 30],
            ['name' => 'analytics.advanced', 'category' => 'analytics', 'action' => 'advanced', 'display_name' => '高度な分析', 'required_level' => 40],

            // システム管理権限
            ['name' => 'system.config', 'category' => 'system', 'action' => 'config', 'display_name' => 'システム設定', 'required_level' => 60, 'is_dangerous' => true],
            ['name' => 'system.maintenance', 'category' => 'system', 'action' => 'maintenance', 'display_name' => 'メンテナンス', 'required_level' => 50, 'is_dangerous' => true],
            ['name' => 'system.logs', 'category' => 'system', 'action' => 'logs', 'display_name' => 'システムログ', 'required_level' => 40],

            // 管理者権限管理（最高レベル）
            ['name' => 'admin.invite', 'category' => 'admin', 'action' => 'invite', 'display_name' => '管理者招待', 'required_level' => 70, 'is_dangerous' => true],
            ['name' => 'admin.roles', 'category' => 'admin', 'action' => 'roles', 'display_name' => 'ロール管理', 'required_level' => 80, 'is_dangerous' => true],
            ['name' => 'admin.permissions', 'category' => 'admin', 'action' => 'permissions', 'display_name' => '権限管理', 'required_level' => 90, 'is_dangerous' => true],
        ];

        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'description' => $permission['display_name'] . 'の権限',
                    'group_name' => $permission['category'],
                    'is_system_permission' => true,
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }

        $this->command->info('権限データを作成しました: ' . count($permissions) . '件');
    }

    /**
     * 管理者ロールの作成（階層型権限システム）
     */
    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'スーパー管理者',
                'description' => 'システム全体の完全な管理権限',
                'level' => 100,
                'permissions' => ['*'], // 全権限
                'is_system_role' => true,
                'can_access_analytics' => true,
                'can_manage_users' => true,
                'can_manage_game_data' => true,
                'can_manage_system' => true,
                'can_invite_admins' => true,
            ],
            [
                'name' => 'admin',
                'display_name' => '管理者',
                'description' => 'ゲーム運営の主要管理権限',
                'level' => 70,
                'permissions' => [
                    'users.*', 'players.*', 'items.*', 'monsters.*', 'shops.*',
                    'analytics.view', 'analytics.export', 'system.logs'
                ],
                'is_system_role' => true,
                'can_access_analytics' => true,
                'can_manage_users' => true,
                'can_manage_game_data' => true,
                'can_manage_system' => false,
                'can_invite_admins' => false,
            ],
            [
                'name' => 'moderator',
                'display_name' => 'モデレーター',
                'description' => 'ユーザー管理とコンテンツ監視',
                'level' => 40,
                'permissions' => [
                    'users.view', 'users.edit', 'users.suspend',
                    'players.view', 'players.edit',
                    'analytics.view'
                ],
                'is_system_role' => true,
                'can_access_analytics' => true,
                'can_manage_users' => true,
                'can_manage_game_data' => false,
                'can_manage_system' => false,
                'can_invite_admins' => false,
            ],
            [
                'name' => 'game_designer',
                'display_name' => 'ゲームデザイナー',
                'description' => 'ゲーム内容・バランス調整',
                'level' => 50,
                'permissions' => [
                    'items.*', 'monsters.*', 'shops.*',
                    'players.view', 'players.stats',
                    'analytics.view', 'analytics.advanced'
                ],
                'is_system_role' => true,
                'can_access_analytics' => true,
                'can_manage_users' => false,
                'can_manage_game_data' => true,
                'can_manage_system' => false,
                'can_invite_admins' => false,
            ],
            [
                'name' => 'analyst',
                'display_name' => 'データアナリスト',
                'description' => 'ゲームデータ分析専用',
                'level' => 30,
                'permissions' => [
                    'analytics.*', 'users.view', 'players.view',
                    'items.view', 'monsters.view', 'shops.view'
                ],
                'is_system_role' => true,
                'can_access_analytics' => true,
                'can_manage_users' => false,
                'can_manage_game_data' => false,
                'can_manage_system' => false,
                'can_invite_admins' => false,
            ],
        ];

        foreach ($roles as $role) {
            DB::table('admin_roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'level' => $role['level'],
                    'permissions' => json_encode($role['permissions']),
                    'is_system_role' => $role['is_system_role'],
                    'can_access_analytics' => $role['can_access_analytics'],
                    'can_manage_users' => $role['can_manage_users'],
                    'can_manage_game_data' => $role['can_manage_game_data'],
                    'can_manage_system' => $role['can_manage_system'],
                    'can_invite_admins' => $role['can_invite_admins'],
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        $this->command->info('ロールデータを作成しました: ' . count($roles) . '件');
    }

    /**
     * 初期管理者アカウントの作成
     */
    private function createInitialAdmin(): void
    {
        // スーパー管理者ロールのIDを取得
        $superAdminRole = DB::table('admin_roles')->where('name', 'super_admin')->first();
        
        if (!$superAdminRole) {
            $this->command->error('スーパー管理者ロールが見つかりません。');
            return;
        }

        // 既存の管理者アカウントをチェック
        $existingAdmin = DB::table('users')->where('is_admin', true)->first();
        
        if ($existingAdmin) {
            // 既存ユーザーを管理者に昇格
            DB::table('users')->where('id', $existingAdmin->id)->update([
                'is_admin' => true,
                'admin_activated_at' => Carbon::now(),
                'admin_role_id' => $superAdminRole->id,
                'admin_level' => 'super',
                'admin_permissions' => json_encode(['*']),
                'admin_permissions_updated_at' => Carbon::now(),
                'admin_notes' => 'システム初期化時にスーパー管理者として設定',
                'updated_at' => Carbon::now(),
            ]);
            
            $this->command->info("既存ユーザー {$existingAdmin->name} ({$existingAdmin->email}) をスーパー管理者に昇格しました。");
        } else {
            // 新規管理者アカウント作成
            $adminId = DB::table('users')->insertGetId([
                'name' => 'System Admin',
                'email' => 'admin@test-smg.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('AdminPassword123!'),
                'is_admin' => true,
                'admin_activated_at' => Carbon::now(),
                'admin_role_id' => $superAdminRole->id,
                'admin_level' => 'super',
                'admin_permissions' => json_encode(['*']),
                'admin_permissions_updated_at' => Carbon::now(),
                'admin_notes' => 'システム初期化時に作成されたスーパー管理者アカウント',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $this->command->info("新規スーパー管理者アカウントを作成しました:");
            $this->command->info("Email: admin@test-smg.com");
            $this->command->info("Password: AdminPassword123!");
            $this->command->warn("セキュリティのため、ログイン後にパスワードを変更してください。");
        }
    }
}
