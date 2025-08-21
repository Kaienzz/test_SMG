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
        Schema::create('monster_spawn_lists', function (Blueprint $table) {
            $table->id();
            $table->string('location_id');
            $table->string('monster_id');
            $table->decimal('spawn_rate', 3, 2); // 0.00 to 1.00
            $table->integer('priority')->default(0);
            $table->integer('min_level')->nullable();
            $table->integer('max_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('location_id')->references('id')->on('game_locations')->onDelete('cascade');
            $table->foreign('monster_id')->references('id')->on('monsters')->onDelete('cascade');
            
            // Indexes
            $table->index('location_id');
            $table->index('monster_id');
            $table->index('is_active');
            $table->index('priority');
            
            // Unique constraint - one monster per location with specific spawn settings
            $table->unique(['location_id', 'monster_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monster_spawn_lists');
    }
};