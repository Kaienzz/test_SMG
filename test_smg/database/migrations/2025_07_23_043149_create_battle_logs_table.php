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
        Schema::create('battle_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('monster_name');
            $table->string('location');
            $table->enum('result', ['victory', 'defeat', 'escaped']);
            $table->integer('experience_gained')->default(0);
            $table->integer('gold_lost')->default(0);
            $table->integer('turns')->default(1);
            $table->json('battle_data')->nullable(); // 詳細な戦闘データ
            $table->timestamps();
            
            // インデックス
            $table->index(['user_id', 'created_at']);
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battle_logs');
    }
};
