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
        Schema::table('game_locations', function (Blueprint $table) {
            $table->string('dungeon_id')->nullable()->after('category');
            $table->index('dungeon_id');
            
            // 外部キー制約（参照整合性のため）
            $table->foreign('dungeon_id')
                  ->references('dungeon_id')
                  ->on('dungeons_desc')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_locations', function (Blueprint $table) {
            $table->dropForeign(['dungeon_id']);
            $table->dropIndex(['dungeon_id']);
            $table->dropColumn('dungeon_id');
        });
    }
};
