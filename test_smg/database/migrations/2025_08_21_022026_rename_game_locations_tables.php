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
        // Drop foreign key constraints first (if they exist)
        if (Schema::hasTable('location_connections')) {
            Schema::table('location_connections', function (Blueprint $table) {
                $table->dropForeign(['source_location_id']);
                $table->dropForeign(['target_location_id']);
            });
        }
        
        if (Schema::hasTable('monster_spawn_lists')) {
            Schema::table('monster_spawn_lists', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
            });
        }

        // Rename tables
        if (Schema::hasTable('game_locations')) {
            Schema::rename('game_locations', 'routes');
        }
        
        if (Schema::hasTable('location_connections')) {
            Schema::rename('location_connections', 'route_connections');
        }

        // Recreate foreign key constraints with new table names
        if (Schema::hasTable('route_connections')) {
            Schema::table('route_connections', function (Blueprint $table) {
                $table->foreign('source_location_id')->references('id')->on('routes')->onDelete('cascade');
                $table->foreign('target_location_id')->references('id')->on('routes')->onDelete('cascade');
            });
        }
        
        if (Schema::hasTable('monster_spawn_lists')) {
            Schema::table('monster_spawn_lists', function (Blueprint $table) {
                $table->foreign('location_id')->references('id')->on('routes')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints first
        if (Schema::hasTable('route_connections')) {
            Schema::table('route_connections', function (Blueprint $table) {
                $table->dropForeign(['source_location_id']);
                $table->dropForeign(['target_location_id']);
            });
        }
        
        if (Schema::hasTable('monster_spawn_lists')) {
            Schema::table('monster_spawn_lists', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
            });
        }

        // Rename tables back
        if (Schema::hasTable('routes')) {
            Schema::rename('routes', 'game_locations');
        }
        
        if (Schema::hasTable('route_connections')) {
            Schema::rename('route_connections', 'location_connections');
        }

        // Recreate original foreign key constraints
        if (Schema::hasTable('location_connections')) {
            Schema::table('location_connections', function (Blueprint $table) {
                $table->foreign('source_location_id')->references('id')->on('game_locations')->onDelete('cascade');
                $table->foreign('target_location_id')->references('id')->on('game_locations')->onDelete('cascade');
            });
        }
        
        if (Schema::hasTable('monster_spawn_lists')) {
            Schema::table('monster_spawn_lists', function (Blueprint $table) {
                $table->foreign('location_id')->references('id')->on('game_locations')->onDelete('cascade');
            });
        }
    }
};
