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
        Schema::create('custom_items', function (Blueprint $table) {
            $table->id();
            
            // ベースとなる標準アイテムのID
            $table->foreignId('base_item_id')->constrained('items')->onDelete('cascade');
            
            // 生産者（錬金実施者）
            $table->foreignId('creator_id')->constrained('players')->onDelete('cascade');
            
            // カスタムステータス
            $table->json('custom_stats');
            
            // ベースステータス（比較用）
            $table->json('base_stats');
            
            // 使用素材効果
            $table->json('material_bonuses');
            
            // ベースアイテムの使用時耐久度
            $table->integer('base_durability');
            
            // 現在耐久度（base_durabilityを基準に計算）
            $table->integer('durability');
            
            // 最大耐久度（素材効果で向上可能）
            $table->integer('max_durability');
            
            // 名匠品フラグ
            $table->boolean('is_masterwork')->default(false);
            
            $table->timestamps();
            
            // インデックス
            $table->index('creator_id', 'idx_creator_custom_items');
            $table->index('base_item_id', 'idx_base_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_items');
    }
};
