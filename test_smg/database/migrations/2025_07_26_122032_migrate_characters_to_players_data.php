<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // データ移行前のバックアップチェック
        $charactersCount = DB::table('characters')->count();
        Log::info("Starting character to player migration. Characters count: {$charactersCount}");
        
        if ($charactersCount > 0) {
            // charactersテーブルからplayersテーブルへデータを移行
            DB::statement("
                INSERT INTO players (
                    user_id, name, level, experience, experience_to_next,
                    attack, defense, agility, evasion, magic_attack, accuracy,
                    hp, max_hp, mp, max_mp, sp, max_sp,
                    base_attack, base_defense, base_agility, base_evasion,
                    base_max_hp, base_max_sp, base_max_mp, base_magic_attack, base_accuracy,
                    location_type, location_id, game_position, last_visited_town,
                    gold,
                    created_at, updated_at
                )
                SELECT 
                    user_id, name, level, experience, experience_to_next,
                    attack, defense, agility, evasion, magic_attack, accuracy,
                    hp, max_hp, mp, max_mp, sp, max_sp,
                    COALESCE(base_attack, attack), COALESCE(base_defense, defense), 
                    COALESCE(base_agility, agility), COALESCE(base_evasion, evasion),
                    COALESCE(base_max_hp, max_hp), COALESCE(base_max_sp, max_sp), 
                    COALESCE(base_max_mp, max_mp), COALESCE(base_magic_attack, magic_attack), 
                    COALESCE(base_accuracy, accuracy),
                    location_type, location_id, game_position, last_visited_town,
                    gold,
                    created_at, updated_at
                FROM characters
                WHERE user_id IS NOT NULL
            ");
            
            $playersCount = DB::table('players')->count();
            Log::info("Data migration completed. Players count: {$playersCount}");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ロールバック用：playersテーブルからcharactersテーブルへデータを戻す
        $playersCount = DB::table('players')->count();
        Log::info("Starting rollback: player to character migration. Players count: {$playersCount}");
        
        if ($playersCount > 0) {
            // charactersテーブルをクリア
            DB::table('characters')->delete();
            
            // playersテーブルからcharactersテーブルへデータを移行
            DB::statement("
                INSERT INTO characters (
                    user_id, name, level, experience, experience_to_next,
                    attack, defense, agility, evasion, magic_attack, accuracy,
                    hp, max_hp, mp, max_mp, sp, max_sp,
                    base_attack, base_defense, base_agility, base_evasion,
                    base_max_hp, base_max_sp, base_max_mp, base_magic_attack, base_accuracy,
                    location_type, location_id, game_position, last_visited_town,
                    gold,
                    created_at, updated_at
                )
                SELECT 
                    user_id, name, level, experience, experience_to_next,
                    attack, defense, agility, evasion, magic_attack, accuracy,
                    hp, max_hp, mp, max_mp, sp, max_sp,
                    base_attack, base_defense, base_agility, base_evasion,
                    base_max_hp, base_max_sp, base_max_mp, base_magic_attack, base_accuracy,
                    location_type, location_id, game_position, last_visited_town,
                    gold,
                    created_at, updated_at
                FROM players
            ");
            
            $charactersCount = DB::table('characters')->count();
            Log::info("Rollback completed. Characters count: {$charactersCount}");
        }
    }
};
