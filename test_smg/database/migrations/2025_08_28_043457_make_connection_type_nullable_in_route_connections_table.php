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
        Schema::table('route_connections', function (Blueprint $table) {
            // Make connection_type nullable for legacy compatibility
            $table->string('connection_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('route_connections', function (Blueprint $table) {
            // Revert connection_type to NOT NULL
            $table->string('connection_type')->nullable(false)->change();
        });
    }
};
