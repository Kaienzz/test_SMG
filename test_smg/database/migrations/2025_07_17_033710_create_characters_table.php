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
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('冒険者');
            $table->integer('experience')->default(0);
            $table->integer('attack')->default(10);
            $table->integer('defense')->default(8);
            $table->integer('agility')->default(12);
            $table->integer('evasion')->default(15);
            $table->integer('hp')->default(100);
            $table->integer('max_hp')->default(100);
            $table->integer('sp')->default(30);
            $table->integer('max_sp')->default(30);
            $table->integer('mp')->default(20);
            $table->integer('max_mp')->default(20);
            $table->integer('magic_attack')->default(8);
            $table->integer('accuracy')->default(85);
            $table->integer('gold')->default(1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
