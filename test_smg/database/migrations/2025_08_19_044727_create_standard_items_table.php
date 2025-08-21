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
        Schema::create('standard_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category'); 
            $table->string('category_name');
            $table->json('effects'); // Store effects as JSON
            $table->integer('value');
            $table->integer('sell_price')->nullable();
            $table->integer('stack_limit')->default(1);
            $table->integer('max_durability')->nullable();
            $table->boolean('is_equippable')->default(false);
            $table->boolean('is_usable')->default(false);
            $table->string('weapon_type')->nullable();
            $table->boolean('is_standard')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('category');
            $table->index('is_equippable');
            $table->index('is_usable');
            $table->index('weapon_type');
            $table->index('is_standard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_items');
    }
};
