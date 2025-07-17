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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->integer('stack_limit')->nullable();
            $table->integer('max_durability')->nullable();
            $table->json('effects')->nullable();
            $table->integer('rarity')->default(1);
            $table->integer('value')->default(0);
            $table->integer('sell_price')->nullable();
            $table->string('battle_skill_id')->nullable();
            $table->string('weapon_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
