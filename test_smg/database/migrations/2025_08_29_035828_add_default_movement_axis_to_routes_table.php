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
        Schema::table('routes', function (Blueprint $table) {
            $table->enum('default_movement_axis', ['horizontal', 'vertical'])
                  ->default('horizontal')
                  ->after('difficulty')
                  ->comment('基本移動軸：horizontal=左右、vertical=上下');
            
            // パフォーマンス向上のためのインデックス追加
            $table->index('default_movement_axis', 'idx_routes_movement_axis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropIndex('idx_routes_movement_axis');
            $table->dropColumn('default_movement_axis');
        });
    }
};
