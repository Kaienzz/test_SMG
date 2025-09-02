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
        // Recreate facility_items to ensure foreign keys reference town_facilities
        if (Schema::hasTable('facility_items')) {
            Schema::drop('facility_items');
        }

        Schema::create('facility_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained('town_facilities')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->integer('price');
            $table->integer('stock')->default(-1);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Best-effort rollback: drop and recreate without FK to allow flexibility
        if (Schema::hasTable('facility_items')) {
            Schema::drop('facility_items');
        }

        Schema::create('facility_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->integer('price');
            $table->integer('stock')->default(-1);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['facility_id', 'item_id']);
        });
    }
};
