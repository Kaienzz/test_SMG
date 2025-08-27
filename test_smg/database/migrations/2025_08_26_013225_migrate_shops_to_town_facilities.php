<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('shops')) {
            $shops = DB::table('shops')->get();
            
            foreach ($shops as $shop) {
                DB::table('town_facilities')->insert([
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'facility_type' => $shop->shop_type,
                    'location_id' => $shop->location_id,
                    'location_type' => $shop->location_type,
                    'is_active' => $shop->is_active,
                    'description' => $shop->description,
                    'facility_config' => $shop->shop_config,
                    'created_at' => $shop->created_at,
                    'updated_at' => $shop->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('town_facilities')->truncate();
    }
};
