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
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('character_id');
            $table->string('skill_type')->default('combat'); // combat, movement, gathering, magic, utility
            $table->string('skill_name');
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0);
            $table->json('effects')->nullable(); // スキル効果
            $table->integer('sp_cost')->default(10);
            $table->integer('duration')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // 外部キー制約
            $table->foreign('character_id')->references('id')->on('characters')->onDelete('cascade');
            
            // インデックス
            $table->index(['character_id', 'skill_name']);
            $table->index('skill_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skills');
    }
};
