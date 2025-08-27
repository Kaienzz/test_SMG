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
        Schema::rename('shop_items', 'facility_items');
        
        Schema::table('facility_items', function (Blueprint $table) {
            $table->renameColumn('shop_id', 'facility_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facility_items', function (Blueprint $table) {
            $table->renameColumn('facility_id', 'shop_id');
        });
        
        Schema::rename('facility_items', 'shop_items');
    }
};
