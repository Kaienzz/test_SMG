<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compounding_recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity');
            $table->timestamps();

            $table->unique(['recipe_id', 'item_id']);
            $table->foreign('recipe_id')->references('id')->on('compounding_recipes')->onDelete('cascade');
            // item_id 外部キーは items テーブルへ（存在しない場合は後で追加）
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compounding_recipe_ingredients');
    }
};
