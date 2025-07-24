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
        Schema::table('characters', function (Blueprint $table) {
            // ユーザーとの関連付け（既存チェック）
            if (!Schema::hasColumn('characters', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            
            // ゲーム進行状況（既存のカラムをスキップ）
            if (!Schema::hasColumn('characters', 'location_type')) {
                $table->string('location_type')->default('town');
            }
            if (!Schema::hasColumn('characters', 'location_id')) {
                $table->string('location_id')->default('town_a');
            }
            if (!Schema::hasColumn('characters', 'game_position')) {
                $table->integer('game_position')->default(0);
            }
            if (!Schema::hasColumn('characters', 'last_visited_town')) {
                $table->string('last_visited_town')->default('town_a');
            }
            
            // 経験値の次レベルまでの値（levelとexperienceは既存）
            if (!Schema::hasColumn('characters', 'experience_to_next')) {
                $table->integer('experience_to_next')->default(100);
            }
        });
        
        // 既存のレコードを削除（テスト環境なので安全）
        DB::table('characters')->delete();
        
        // user_idをNOT NULLに変更し、外部キー制約を追加
        Schema::table('characters', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (Schema::hasColumn('characters', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            }
            
            // 新しく追加したカラムのみ削除
            $columnsToCheck = [
                'location_type',
                'location_id', 
                'game_position',
                'last_visited_town',
                'experience_to_next'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
