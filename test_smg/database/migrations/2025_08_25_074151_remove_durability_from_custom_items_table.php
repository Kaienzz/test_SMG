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
        Schema::table('custom_items', function (Blueprint $table) {
            // 現在の耐久度フィールドを削除（マスターデータ化のため）
            // 個別の耐久度管理はインベントリシステムで行う
            $table->dropColumn('durability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_items', function (Blueprint $table) {
            // ロールバック時にdurabilityカラムを復元
            $table->integer('durability')->after('base_durability');
        });
    }
};
