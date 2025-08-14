<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 管理者システム基本機能
            $table->boolean('is_admin')->default(false)->after('email_verified_at');
            $table->timestamp('admin_activated_at')->nullable()->after('is_admin');
            $table->timestamp('admin_last_login_at')->nullable()->after('admin_activated_at');
            $table->unsignedBigInteger('admin_role_id')->nullable()->after('admin_last_login_at');
            
            // セキュリティ強化
            $table->json('admin_permissions')->nullable()->after('admin_role_id');
            $table->string('admin_level', 20)->default('basic')->after('admin_permissions'); // basic, advanced, super
            $table->boolean('admin_requires_2fa')->default(false)->after('admin_level');
            $table->json('admin_ip_whitelist')->nullable()->after('admin_requires_2fa');
            
            // 管理活動追跡
            $table->timestamp('admin_permissions_updated_at')->nullable()->after('admin_ip_whitelist');
            $table->unsignedBigInteger('admin_created_by')->nullable()->after('admin_permissions_updated_at');
            $table->text('admin_notes')->nullable()->after('admin_created_by');
            
            // インデックス追加（パフォーマンス向上）
            $table->index(['is_admin', 'admin_activated_at'], 'idx_active_admins');
            $table->index('admin_role_id', 'idx_admin_role');
            $table->index('admin_level', 'idx_admin_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // インデックス削除
            $table->dropIndex('idx_active_admins');
            $table->dropIndex('idx_admin_role');
            $table->dropIndex('idx_admin_level');
            
            // カラム削除
            $table->dropColumn([
                'is_admin',
                'admin_activated_at',
                'admin_last_login_at',
                'admin_role_id',
                'admin_permissions',
                'admin_level',
                'admin_requires_2fa',
                'admin_ip_whitelist',
                'admin_permissions_updated_at',
                'admin_created_by',
                'admin_notes'
            ]);
        });
    }
};
