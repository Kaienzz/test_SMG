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
        // 新規プレイヤーのデフォルト値を1,000Gに戻す
        Schema::table('players', function (Blueprint $table) {
            $table->integer('gold')->default(1000)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 元のデフォルト値（1,001,000G）に戻す
        Schema::table('players', function (Blueprint $table) {
            $table->integer('gold')->default(1001000)->change();
        });
    }
};
