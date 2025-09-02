<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compounding_recipe_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recipe_id');
            $table->string('location_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['recipe_id', 'location_id']);
            $table->index('location_id');
            $table->index('is_active');
            $table->foreign('recipe_id')->references('id')->on('compounding_recipes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compounding_recipe_locations');
    }
};
