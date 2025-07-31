<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既存のプレイヤーに1,000,000G追加
        DB::table('players')->increment('gold', 1000000);
        
        // 今後のプレイヤーのデフォルト値も変更
        Schema::table('players', function (Blueprint $table) {
            $table->integer('gold')->default(1001000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 既存のプレイヤーから1,000,000G減額
        DB::table('players')->decrement('gold', 1000000);
        
        // デフォルト値を元に戻す
        Schema::table('players', function (Blueprint $table) {
            $table->integer('gold')->default(1000)->change();
        });
    }
};
