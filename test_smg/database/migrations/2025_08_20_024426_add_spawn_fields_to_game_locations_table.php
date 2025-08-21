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
        Schema::table('game_locations', function (Blueprint $table) {
            // 旧SpawnListから移行するフィールド
            $table->json('spawn_tags')->nullable()->after('spawn_list_id');
            $table->text('spawn_description')->nullable()->after('spawn_tags');
            
            // 統合後は spawn_list_id は不要になるが、段階的移行のため残しておく
            // 将来的には spawn_list_id カラムを削除する予定
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropColumn(['spawn_tags', 'spawn_description']);
        });
    }
};