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
        Schema::create('game_locations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['road', 'town', 'dungeon']); 
            $table->integer('length')->nullable(); // For roads and dungeons
            $table->enum('difficulty', ['easy', 'normal', 'hard'])->nullable();
            $table->decimal('encounter_rate', 3, 2)->nullable(); // 0.00 to 1.00
            $table->string('spawn_list_id')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Additional fields for different types
            $table->string('type')->nullable(); // town_type, dungeon_type, etc.
            $table->json('services')->nullable(); // For towns: shop, inn, etc.
            $table->json('special_actions')->nullable(); // Special events/actions
            $table->json('branches')->nullable(); // Road branches
            $table->integer('floors')->nullable(); // For dungeons
            $table->integer('min_level')->nullable();
            $table->integer('max_level')->nullable();
            $table->string('boss')->nullable(); // Dungeon boss
            
            $table->timestamps();
            
            // Indexes
            $table->index('category');
            $table->index('difficulty');
            $table->index('spawn_list_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_locations');
    }
};
