<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compounding_recipes', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_key')->unique();
            $table->string('name');
            $table->unsignedBigInteger('product_item_id');
            $table->integer('product_quantity')->default(1);
            $table->integer('required_skill_level')->default(1);
            $table->integer('success_rate')->default(100);
            $table->integer('sp_cost')->default(15);
            $table->integer('base_exp')->default(100);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_item_id');
            $table->index('is_active');
            $table->index('required_skill_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compounding_recipes');
    }
};
