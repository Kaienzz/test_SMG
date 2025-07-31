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
        Schema::create('alchemy_materials', function (Blueprint $table) {
            $table->id();
            
            // 素材アイテム名
            $table->string('item_name')->unique('unique_material');
            
            // ステータス効果
            $table->json('stat_bonuses');
            
            // 耐久度ボーナス
            $table->integer('durability_bonus')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alchemy_materials');
    }
};
