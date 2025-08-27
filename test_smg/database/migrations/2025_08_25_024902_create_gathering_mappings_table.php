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
        Schema::create('gathering_mappings', function (Blueprint $table) {
            $table->id();
            
            // 外部キー
            $table->string('route_id');
            $table->unsignedBigInteger('item_id');
            
            // 基本採集設定
            $table->integer('required_skill_level')->default(1);
            $table->integer('success_rate'); // 1-100
            $table->integer('quantity_min')->default(1);
            $table->integer('quantity_max')->default(1);
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // インデックス
            $table->index('route_id', 'idx_gathering_route_id');
            $table->index('item_id', 'idx_gathering_item_id');
            $table->index('required_skill_level', 'idx_gathering_skill_level');
            $table->index('is_active', 'idx_gathering_active');
            
            // 外部キー制約
            $table->foreign('route_id')
                  ->references('id')
                  ->on('routes')
                  ->onDelete('cascade');
            
            $table->foreign('item_id')
                  ->references('id')
                  ->on('items')
                  ->onDelete('cascade');
            
            // ユニーク制約（1つのルートに同じアイテムは1つまで）
            $table->unique(['route_id', 'item_id'], 'unique_route_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gathering_mappings');
    }
};
