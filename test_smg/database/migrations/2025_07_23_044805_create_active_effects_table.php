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
        Schema::create('active_effects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('effect_name');
            $table->json('effects'); // 効果の詳細
            $table->integer('remaining_duration');
            $table->string('source_type')->default('skill'); // skill, item, battle
            $table->unsignedBigInteger('source_id')->nullable(); // スキルIDなど
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            
            // インデックス
            $table->index(['character_id', 'is_active']);
            $table->index('effect_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_effects');
    }
};
