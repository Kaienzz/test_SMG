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
        Schema::create('active_battles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('battle_id')->unique();
            $table->json('character_data');
            $table->json('monster_data');
            $table->json('battle_log')->default('[]');
            $table->integer('turn')->default(1);
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, paused, completed
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_battles');
    }
};
