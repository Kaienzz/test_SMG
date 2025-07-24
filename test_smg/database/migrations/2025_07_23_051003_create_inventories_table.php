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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->json('slot_data')->default('[]'); // インベントリスロットデータ
            $table->integer('max_slots')->default(10); // 最大スロット数
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            
            // インデックス
            $table->unique('character_id'); // 1キャラクター1インベントリ
            $table->index('character_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
