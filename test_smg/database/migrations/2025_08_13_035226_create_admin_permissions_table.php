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
        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            
            // 権限識別子
            $table->string('name', 100)->unique(); // 'users.create', 'players.edit', 'analytics.view', etc.
            $table->string('category', 50); // 'users', 'players', 'items', 'analytics', 'system'
            $table->string('action', 50); // 'create', 'read', 'update', 'delete', 'manage'
            
            // 表示用情報
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            
            // 権限レベル設定
            $table->unsignedTinyInteger('required_level')->default(1); // 必要最小権限レベル
            $table->boolean('is_dangerous')->default(false); // 危険な操作（削除、システム変更等）
            
            // グループ化・階層化
            $table->string('group_name', 50)->nullable(); // 権限のグループ化
            $table->unsignedBigInteger('parent_permission_id')->nullable(); // 階層的権限
            
            // リソース制限（将来拡張用）
            $table->json('resource_constraints')->nullable(); // 特定リソースへの制限
            $table->json('conditions')->nullable(); // 条件付き権限
            
            // 国際化対応
            $table->json('localized_names')->nullable();
            $table->json('localized_descriptions')->nullable();
            
            // システム管理
            $table->boolean('is_system_permission')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('deprecated_at')->nullable();
            
            // 作成・更新追跡
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            // インデックス（検索・パフォーマンス最適化）
            $table->index(['category', 'action'], 'idx_permission_category_action');
            $table->index(['required_level', 'is_active'], 'idx_permission_level_active');
            $table->index('group_name', 'idx_permission_group');
            $table->index('is_dangerous', 'idx_dangerous_permissions');
            $table->index('parent_permission_id', 'idx_parent_permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
    }
};
