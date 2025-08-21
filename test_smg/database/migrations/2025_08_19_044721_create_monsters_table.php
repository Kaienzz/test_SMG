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
        Schema::create('monsters', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('level');
            $table->integer('hp');
            $table->integer('max_hp');
            $table->integer('attack');
            $table->integer('defense');
            $table->integer('agility');
            $table->integer('evasion');
            $table->integer('accuracy');
            $table->integer('experience_reward');
            $table->string('emoji', 10)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('level');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monsters');
    }
};
