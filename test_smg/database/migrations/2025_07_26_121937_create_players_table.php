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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            
            // ユーザーとの関連付け（1:1関係）
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // 基本情報
            $table->string('name')->default('冒険者');
            
            // レベル・経験値システム
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0);
            $table->integer('experience_to_next')->default(100);
            
            // 戦闘ステータス（現在値）
            $table->integer('attack')->default(10);
            $table->integer('defense')->default(8);
            $table->integer('agility')->default(12);
            $table->integer('evasion')->default(15);
            $table->integer('magic_attack')->default(8);
            $table->integer('accuracy')->default(85);
            
            // リソース管理（HP/MP/SP）
            $table->integer('hp')->default(100);
            $table->integer('max_hp')->default(100);
            $table->integer('mp')->default(20);
            $table->integer('max_mp')->default(20);
            $table->integer('sp')->default(30);
            $table->integer('max_sp')->default(30);
            
            // ベースステータス（装備・スキル効果前の基礎値）
            $table->integer('base_attack')->default(10);
            $table->integer('base_defense')->default(8);
            $table->integer('base_agility')->default(12);
            $table->integer('base_evasion')->default(15);
            $table->integer('base_max_hp')->default(100);
            $table->integer('base_max_sp')->default(30);
            $table->integer('base_max_mp')->default(20);
            $table->integer('base_magic_attack')->default(8);
            $table->integer('base_accuracy')->default(85);
            
            // ゲーム進行状況
            $table->string('location_type')->default('town');
            $table->string('location_id')->default('town_a');
            $table->integer('game_position')->default(0);
            $table->string('last_visited_town')->default('town_a');
            
            // 経済
            $table->integer('gold')->default(1000);
            
            // JSONデータ（将来拡張用）
            $table->json('location_data')->nullable();
            $table->json('player_data')->nullable();
            $table->json('game_data')->nullable();
            
            $table->timestamps();
            
            // インデックス
            $table->index(['location_type', 'location_id'], 'idx_location');
            $table->index('level', 'idx_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
