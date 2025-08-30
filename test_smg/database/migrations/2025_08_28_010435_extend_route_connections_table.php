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
        Schema::table('route_connections', function (Blueprint $table) {
            // Position fields
            $table->tinyInteger('source_position')->nullable()->after('direction');
            $table->tinyInteger('target_position')->nullable()->after('source_position');
            
            // Edge classification and status
            $table->enum('edge_type', ['normal', 'branch', 'portal', 'exit', 'enter'])
                  ->nullable()->after('target_position');
            $table->boolean('is_enabled')->default(true)->after('edge_type');
            
            // Action and interaction
            $table->enum('action_label', [
                'turn_right', 'turn_left', 
                'move_north', 'move_south', 'move_west', 'move_east',
                'enter_dungeon', 'exit_dungeon'
            ])->nullable()->after('is_enabled');
            
            $table->enum('keyboard_shortcut', ['up', 'down', 'left', 'right'])
                  ->nullable()->after('action_label');
            
            // Indexes for performance
            $table->index(['source_location_id', 'source_position'], 'idx_route_source_pos');
            $table->index(['source_location_id', 'keyboard_shortcut'], 'idx_route_keyboard');
            
            // Unique constraint for keyboard shortcuts per source location
            $table->unique(['source_location_id', 'keyboard_shortcut'], 'uniq_keyboard_per_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_connections', function (Blueprint $table) {
            // Drop constraints and indexes first
            $table->dropUnique('uniq_keyboard_per_source');
            $table->dropIndex('idx_route_keyboard');
            $table->dropIndex('idx_route_source_pos');
            
            // Drop columns in reverse order
            $table->dropColumn([
                'keyboard_shortcut',
                'action_label', 
                'is_enabled',
                'edge_type',
                'target_position',
                'source_position'
            ]);
        });
    }
};
