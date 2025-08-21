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
        Schema::table('location_connections', function (Blueprint $table) {
            // Drop and recreate the connection_type column as string
            $table->string('connection_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_connections', function (Blueprint $table) {
            // Revert back to enum (though this might not work perfectly)
            $table->enum('connection_type', ['start', 'end', 'branch', 'town_connection'])->change();
        });
    }
};
