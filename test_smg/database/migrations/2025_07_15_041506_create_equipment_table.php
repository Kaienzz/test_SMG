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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->onDelete('cascade');
            $table->foreignId('weapon_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('body_armor_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('shield_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('helmet_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('boots_id')->nullable()->constrained('items')->onDelete('set null');
            $table->foreignId('accessory_id')->nullable()->constrained('items')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
