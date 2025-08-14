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
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // 操作者情報
            $table->unsignedBigInteger('admin_user_id');
            $table->string('admin_email', 255); // 冗長化（ユーザー削除対策）
            $table->string('admin_name', 255)->nullable();
            
            // 操作詳細
            $table->string('action', 100); // 'user.create', 'player.edit', 'item.delete', etc.
            $table->string('action_category', 50); // 'users', 'players', 'items', 'system'
            $table->text('description'); // 人が読める形での操作説明
            
            // 対象リソース
            $table->string('resource_type', 100)->nullable(); // 'User', 'Player', 'Item', etc.
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('resource_data')->nullable(); // 操作対象の詳細データ
            
            // 操作前後のデータ（変更追跡）
            $table->json('old_values')->nullable(); // 変更前の値
            $table->json('new_values')->nullable(); // 変更後の値
            $table->json('request_data')->nullable(); // リクエストデータ
            
            // セキュリティ情報
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->json('request_headers')->nullable(); // セキュリティ解析用
            
            // 操作結果
            $table->enum('status', ['success', 'failed', 'error'])->default('success');
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable();
            
            // 重要度・分類
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_security_event')->default(false);
            $table->boolean('requires_review')->default(false);
            
            // メタデータ
            $table->string('event_uuid', 36)->unique(); // ユニークな操作ID
            $table->timestamp('event_time')->useCurrent(); // 操作実行時刻
            $table->json('tags')->nullable(); // 検索・分類用タグ
            
            // 関連操作（トランザクション追跡）
            $table->string('batch_id', 36)->nullable(); // 一括操作のID
            $table->unsignedBigInteger('parent_log_id')->nullable(); // 関連する親操作
            
            // アーカイブ・保持期間
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // データ保持期限
            
            $table->timestamps();
            
            // インデックス（検索・パフォーマンス最適化）
            $table->index(['admin_user_id', 'event_time'], 'idx_admin_time');
            $table->index(['action_category', 'action'], 'idx_action_category');
            $table->index(['resource_type', 'resource_id'], 'idx_resource');
            $table->index(['severity', 'is_security_event'], 'idx_security_severity');
            $table->index('event_time', 'idx_event_time');
            $table->index('batch_id', 'idx_batch_operations');
            $table->index(['status', 'requires_review'], 'idx_status_review');
            $table->index('ip_address', 'idx_ip_address');
            
            // 外部キー制約
            $table->foreign('admin_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_log_id')->references('id')->on('admin_audit_logs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
