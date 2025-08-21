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
        Schema::create('monster_spawns', function (Blueprint $table) {
            $table->id();
            $table->string('spawn_list_id');
            $table->string('monster_id');
            $table->decimal('spawn_rate', 3, 2); // 0.00 to 1.00
            $table->integer('priority')->default(0);
            $table->integer('min_level')->nullable();
            $table->integer('max_level')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('spawn_list_id')->references('id')->on('spawn_lists')->onDelete('cascade');
            $table->foreign('monster_id')->references('id')->on('monsters')->onDelete('cascade');
            
            // Indexes
            $table->index('spawn_list_id');
            $table->index('monster_id');
            $table->index('is_active');
            $table->index('priority');
            
            // Unique constraint
            $table->unique(['spawn_list_id', 'monster_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monster_spawns');
    }
};
