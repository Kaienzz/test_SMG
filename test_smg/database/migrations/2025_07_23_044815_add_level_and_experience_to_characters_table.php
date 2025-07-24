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
            // レベルと経験値のカラムを追加（既存チェック）
            if (!Schema::hasColumn('characters', 'level')) {
                $table->integer('level')->default(1)->after('gold');
            }
            if (!Schema::hasColumn('characters', 'experience')) {
                $table->integer('experience')->default(0)->after('level');
            }
            
            // ベースステータスのカラムを追加
            if (!Schema::hasColumn('characters', 'base_attack')) {
                $table->integer('base_attack')->default(10)->after('experience_to_next');
            }
            if (!Schema::hasColumn('characters', 'base_defense')) {
                $table->integer('base_defense')->default(8)->after('base_attack');
            }
            if (!Schema::hasColumn('characters', 'base_agility')) {
                $table->integer('base_agility')->default(12)->after('base_defense');
            }
            if (!Schema::hasColumn('characters', 'base_evasion')) {
                $table->integer('base_evasion')->default(15)->after('base_agility');
            }
            if (!Schema::hasColumn('characters', 'base_max_hp')) {
                $table->integer('base_max_hp')->default(100)->after('base_evasion');
            }
            if (!Schema::hasColumn('characters', 'base_max_sp')) {
                $table->integer('base_max_sp')->default(30)->after('base_max_hp');
            }
            if (!Schema::hasColumn('characters', 'base_max_mp')) {
                $table->integer('base_max_mp')->default(20)->after('base_max_sp');
            }
            if (!Schema::hasColumn('characters', 'base_magic_attack')) {
                $table->integer('base_magic_attack')->default(8)->after('base_max_mp');
            }
            if (!Schema::hasColumn('characters', 'base_accuracy')) {
                $table->integer('base_accuracy')->default(85)->after('base_magic_attack');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            $columnsToCheck = [
                'level',
                'experience',
                'base_attack',
                'base_defense',
                'base_agility',
                'base_evasion',
                'base_max_hp',
                'base_max_sp',
                'base_max_mp',
                'base_magic_attack',
                'base_accuracy'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
