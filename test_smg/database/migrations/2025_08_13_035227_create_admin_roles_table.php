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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            
            // 基本情報
            $table->string('name', 50)->unique(); // super_admin, admin, moderator, analyst, etc.
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            
            // 権限レベル（数値が高いほど強い権限）
            $table->unsignedTinyInteger('level')->default(1); // 1-100
            $table->boolean('is_system_role')->default(false); // システム定義ロール（削除不可）
            
            // 権限設定（JSON形式で柔軟な権限管理）
            $table->json('permissions'); // ['users.read', 'users.edit', 'players.manage', etc.]
            $table->json('restrictions')->nullable(); // 制限事項
            
            // アクセス制御
            $table->boolean('can_access_analytics')->default(false);
            $table->boolean('can_manage_users')->default(false);
            $table->boolean('can_manage_game_data')->default(false);
            $table->boolean('can_manage_system')->default(false);
            $table->boolean('can_invite_admins')->default(false);
            
            // 国際化対応
            $table->json('localized_names')->nullable(); // 多言語対応
            
            // アクティブ状態管理
            $table->boolean('is_active')->default(true);
            $table->timestamp('deprecated_at')->nullable();
            
            // 作成・更新追跡
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            // インデックス
            $table->index(['is_active', 'level'], 'idx_active_roles_by_level');
            $table->index('level', 'idx_role_level');
            $table->index('is_system_role', 'idx_system_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }
};
