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
        Schema::table('characters', function (Blueprint $table) {
            // Check if columns don't already exist before adding them
            if (!Schema::hasColumn('characters', 'mp')) {
                $table->integer('mp')->default(20)->after('max_sp');
            }
            if (!Schema::hasColumn('characters', 'max_mp')) {
                $table->integer('max_mp')->default(20)->after('mp');
            }
            if (!Schema::hasColumn('characters', 'magic_attack')) {
                $table->integer('magic_attack')->default(8)->after('accuracy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $table->dropColumn(['mp', 'max_mp', 'magic_attack']);
        });
    }
};
