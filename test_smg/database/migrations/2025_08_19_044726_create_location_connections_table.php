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
        Schema::create('location_connections', function (Blueprint $table) {
            $table->id();
            $table->string('source_location_id');
            $table->string('target_location_id');
            $table->enum('connection_type', ['start', 'end', 'branch', 'town_connection']);
            $table->integer('position')->nullable(); // For branches at specific positions
            $table->string('direction')->nullable(); // north, south, east, west, straight, left, right
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('source_location_id')->references('id')->on('game_locations')->onDelete('cascade');
            $table->foreign('target_location_id')->references('id')->on('game_locations')->onDelete('cascade');
            
            // Indexes
            $table->index('source_location_id');
            $table->index('target_location_id');
            $table->index('connection_type');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_connections');
    }
};
