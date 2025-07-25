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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shop_type');
            $table->string('location_id');
            $table->string('location_type');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('shop_config')->nullable();
            $table->timestamps();

            $table->unique(['location_id', 'location_type', 'shop_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
