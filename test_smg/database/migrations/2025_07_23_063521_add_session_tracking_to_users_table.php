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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('email_verified_at');
            $table->string('last_device_type')->nullable()->after('last_active_at');
            $table->string('last_ip_address')->nullable()->after('last_device_type');
            $table->json('session_data')->nullable()->after('last_ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_active_at', 'last_device_type', 'last_ip_address', 'session_data']);
        });
    }
};
