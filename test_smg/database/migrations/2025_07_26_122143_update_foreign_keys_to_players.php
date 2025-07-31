<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // skills テーブルの外部キーを更新
        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->renameColumn('character_id', 'player_id');
        });
        
        Schema::table('skills', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->dropIndex(['character_id', 'skill_name']);
            $table->index(['player_id', 'skill_name']);
        });

        // inventories テーブルの外部キーを更新
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->renameColumn('character_id', 'player_id');
        });
        
        Schema::table('inventories', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->dropUnique(['character_id']);
            $table->unique('player_id');
        });

        // active_effects テーブルの外部キーを更新
        Schema::table('active_effects', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->renameColumn('character_id', 'player_id');
        });
        
        Schema::table('active_effects', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        // equipment テーブルの外部キーを更新（foreignIdで作成されているため特殊処理）
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['character_id']);
            $table->renameColumn('character_id', 'player_id');
        });
        
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        // 関連データを players テーブルIDに更新
        $this->updateRelatedTableData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: player_id を character_id に戻す
        
        // skills テーブル
        Schema::table('skills', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->renameColumn('player_id', 'character_id');
        });
        
        Schema::table('skills', function (Blueprint $table) {
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->dropIndex(['player_id', 'skill_name']);
            $table->index(['character_id', 'skill_name']);
        });

        // inventories テーブル
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->renameColumn('player_id', 'character_id');
        });
        
        Schema::table('inventories', function (Blueprint $table) {
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            $table->dropUnique(['player_id']);
            $table->unique('character_id');
        });

        // active_effects テーブル
        Schema::table('active_effects', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->renameColumn('player_id', 'character_id');
        });
        
        Schema::table('active_effects', function (Blueprint $table) {
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });

        // equipment テーブル
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->renameColumn('player_id', 'character_id');
        });
        
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
        });
    }

    /**
     * 関連テーブルのデータIDを players テーブルに合わせて更新
     */
    private function updateRelatedTableData(): void
    {
        // charactersテーブルとplayersテーブルのIDマッピングを作成
        $mappings = DB::table('characters as c')
            ->join('players as p', 'c.user_id', '=', 'p.user_id')
            ->select('c.id as character_id', 'p.id as player_id')
            ->get();

        foreach ($mappings as $mapping) {
            // skills テーブル更新
            DB::table('skills')
                ->where('player_id', $mapping->character_id)
                ->update(['player_id' => $mapping->player_id]);

            // inventories テーブル更新
            DB::table('inventories')
                ->where('player_id', $mapping->character_id)
                ->update(['player_id' => $mapping->player_id]);

            // active_effects テーブル更新
            DB::table('active_effects')
                ->where('player_id', $mapping->character_id)
                ->update(['player_id' => $mapping->player_id]);

            // equipment テーブル更新
            DB::table('equipment')
                ->where('player_id', $mapping->character_id)
                ->update(['player_id' => $mapping->player_id]);
        }
    }
};