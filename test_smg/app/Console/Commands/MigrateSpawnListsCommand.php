<?php

namespace App\Console\Commands;

use App\Models\SpawnList;
use App\Models\MonsterSpawn;
use App\Models\Route;
use App\Models\MonsterSpawnList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateSpawnListsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spawn:migrate 
                           {--dry-run : 実際の移行は行わず、プレビューのみ表示}
                           {--backup : 移行前にデータをバックアップ}
                           {--force : 確認プロンプトをスキップ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SpawnListとMonsterSpawnを新しいMonsterSpawnListに統合移行';

    private array $migrationStats = [];
    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 SpawnList統合マイグレーション開始');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // 事前チェック
        if (!$this->performPreChecks()) {
            return 1;
        }

        // ドライランモード
        if ($this->option('dry-run')) {
            $this->info('📋 ドライランモード: 実際の変更は行いません');
            return $this->performDryRun();
        }

        // バックアップ作成
        if ($this->option('backup')) {
            $this->createBackup();
        }

        // 確認プロンプト
        if (!$this->option('force') && !$this->confirmMigration()) {
            $this->info('❌ 移行がキャンセルされました');
            return 0;
        }

        // 実際の移行実行
        return $this->performMigration();
    }

    /**
     * 事前チェック
     */
    private function performPreChecks(): bool
    {
        $this->info('🔍 事前チェック実行中...');

        // テーブル存在チェック
        $requiredTables = ['spawn_lists', 'monster_spawns', 'routes', 'monsters'];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->error("❌ 必要なテーブル '$table' が存在しません");
                return false;
            }
        }

        // 新テーブルの存在チェック
        if (!DB::getSchemaBuilder()->hasTable('monster_spawn_lists')) {
            $this->error('❌ 新しいテーブル monster_spawn_lists が存在しません。マイグレーションを実行してください');
            return false;
        }

        // 新フィールドの存在チェック
        if (!DB::getSchemaBuilder()->hasColumn('routes', 'spawn_tags')) {
            $this->error('❌ routesテーブルに spawn_tags フィールドがありません。マイグレーションを実行してください');
            return false;
        }

        // データ整合性チェック
        $spawnListCount = SpawnList::count();
        $monsterSpawnCount = MonsterSpawn::count();
        $locationCount = Route::whereNotNull('spawn_list_id')->count();

        $this->info("✅ SpawnList数: {$spawnListCount}");
        $this->info("✅ MonsterSpawn数: {$monsterSpawnCount}");
        $this->info("✅ スポーン設定済みLocation数: {$locationCount}");

        return true;
    }

    /**
     * ドライラン実行
     */
    private function performDryRun(): int
    {
        $this->info('📊 移行対象データの分析...');

        $spawnLists = SpawnList::with(['gameLocations', 'monsterSpawns.monster'])->get();
        
        $this->table(
            ['SpawnList ID', 'Name', 'Locations', 'Monster Spawns', 'Tags'],
            $spawnLists->map(function ($spawnList) {
                return [
                    $spawnList->id,
                    $spawnList->name,
                    $spawnList->gameLocations->count(),
                    $spawnList->monsterSpawns->count(),
                    implode(', ', $spawnList->tags ?? [])
                ];
            })->toArray()
        );

        $totalMonsterSpawns = 0;
        foreach ($spawnLists as $spawnList) {
            $totalMonsterSpawns += $spawnList->monsterSpawns->count();
        }

        $this->info("📈 移行予定データ:");
        $this->info("   - SpawnList -> Route統合: {$spawnLists->count()}件");
        $this->info("   - MonsterSpawn -> MonsterSpawnList移行: {$totalMonsterSpawns}件");

        return 0;
    }

    /**
     * バックアップ作成
     */
    private function createBackup(): void
    {
        $this->info('💾 バックアップ作成中...');
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupData = [
            'spawn_lists' => SpawnList::all()->toArray(),
            'monster_spawns' => MonsterSpawn::all()->toArray(),
            'game_locations_spawn_fields' => Route::select('id', 'spawn_list_id')->get()->toArray(),
            'created_at' => $timestamp,
        ];

        $backupPath = storage_path("app/backups/spawn_migration_backup_{$timestamp}.json");
        
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }

        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("✅ バックアップ作成完了: {$backupPath}");
    }

    /**
     * 移行確認プロンプト
     */
    private function confirmMigration(): bool
    {
        $this->warn('⚠️  重要: この操作は既存のSpawnListとMonsterSpawnデータを変更します');
        $this->warn('   統合後は旧システムは使用できなくなります');
        
        return $this->confirm('移行を実行しますか？');
    }

    /**
     * 実際の移行実行
     */
    private function performMigration(): int
    {
        $this->info('🚀 移行開始...');

        try {
            DB::beginTransaction();

            $this->migrationStats = [
                'spawn_lists_processed' => 0,
                'monster_spawns_migrated' => 0,
                'locations_updated' => 0,
                'errors' => 0,
            ];

            // Phase 1: SpawnListデータをRouteに移行
            $this->migrateSpawnListsToRoutes();

            // Phase 2: MonsterSpawnデータをMonsterSpawnListに移行
            $this->migrateMonsterSpawnsToNewTable();

            // Phase 3: データ整合性チェック
            $this->validateMigrationResult();

            DB::commit();

            $this->displayMigrationSummary();
            $this->info('✅ 移行が正常に完了しました');

            return 0;

        } catch (\Exception $e) {
            DB::rollback();
            
            $this->error('❌ 移行中にエラーが発生しました: ' . $e->getMessage());
            Log::error('SpawnList migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * SpawnListデータをRouteに移行
     */
    private function migrateSpawnListsToRoutes(): void
    {
        $this->info('📋 Phase 1: SpawnListデータの移行...');

        $spawnLists = SpawnList::with('gameLocations')->get();
        $progressBar = $this->output->createProgressBar($spawnLists->count());

        foreach ($spawnLists as $spawnList) {
            try {
                foreach ($spawnList->gameLocations as $location) {
                    $location->update([
                        'spawn_tags' => $spawnList->tags,
                        'spawn_description' => $spawnList->description,
                    ]);

                    $this->migrationStats['locations_updated']++;
                }

                $this->migrationStats['spawn_lists_processed']++;
                $progressBar->advance();

            } catch (\Exception $e) {
                $this->errors[] = "SpawnList {$spawnList->id} 移行エラー: " . $e->getMessage();
                $this->migrationStats['errors']++;
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * MonsterSpawnデータをMonsterSpawnListに移行
     */
    private function migrateMonsterSpawnsToNewTable(): void
    {
        $this->info('📋 Phase 2: MonsterSpawnデータの移行...');

        $monsterSpawns = MonsterSpawn::with(['spawnList.gameLocations'])->get();
        $progressBar = $this->output->createProgressBar($monsterSpawns->count());

        foreach ($monsterSpawns as $monsterSpawn) {
            try {
                // SpawnListが使用されているLocationを取得
                foreach ($monsterSpawn->spawnList->gameLocations as $location) {
                    MonsterSpawnList::create([
                        'location_id' => $location->id,
                        'monster_id' => $monsterSpawn->monster_id,
                        'spawn_rate' => $monsterSpawn->spawn_rate,
                        'priority' => $monsterSpawn->priority,
                        'min_level' => $monsterSpawn->min_level,
                        'max_level' => $monsterSpawn->max_level,
                        'is_active' => $monsterSpawn->is_active,
                    ]);

                    $this->migrationStats['monster_spawns_migrated']++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->errors[] = "MonsterSpawn {$monsterSpawn->id} 移行エラー: " . $e->getMessage();
                $this->migrationStats['errors']++;
            }
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * 移行結果の検証
     */
    private function validateMigrationResult(): void
    {
        $this->info('🔍 移行結果の検証中...');

        // 移行データ数の確認
        $newSpawnCount = MonsterSpawnList::count();
        $originalSpawnCount = MonsterSpawn::count();

        if ($newSpawnCount !== $originalSpawnCount) {
            throw new \Exception("移行データ数が不一致: 元{$originalSpawnCount}件 → 新{$newSpawnCount}件");
        }

        // サンプルデータの整合性チェック
        $sampleLocation = Route::with(['monsterSpawns.monster', 'spawnList.monsterSpawns.monster'])
                                     ->whereNotNull('spawn_list_id')
                                     ->first();

        if ($sampleLocation) {
            $oldSpawns = $sampleLocation->spawnList->monsterSpawns ?? collect();
            $newSpawns = $sampleLocation->monsterSpawns;

            if ($oldSpawns->count() !== $newSpawns->count()) {
                throw new \Exception("サンプルLocation {$sampleLocation->id} のスポーン数が不一致");
            }
        }

        $this->info('✅ データ整合性チェック完了');
    }

    /**
     * 移行サマリー表示
     */
    private function displayMigrationSummary(): void
    {
        $this->info('📊 移行サマリー:');
        $this->table(
            ['項目', '件数'],
            [
                ['処理済みSpawnList', $this->migrationStats['spawn_lists_processed']],
                ['更新済みRoute', $this->migrationStats['locations_updated']],
                ['移行済みMonsterSpawn', $this->migrationStats['monster_spawns_migrated']],
                ['エラー数', $this->migrationStats['errors']],
            ]
        );

        if (!empty($this->errors)) {
            $this->error('⚠️  エラー詳細:');
            foreach ($this->errors as $error) {
                $this->error("   {$error}");
            }
        }

        // パフォーマンステスト
        $this->performanceTest();
    }

    /**
     * パフォーマンステスト
     */
    private function performanceTest(): void
    {
        $this->info('🚀 パフォーマンステスト実行...');

        // 旧システムでのクエリ時間測定
        $start = microtime(true);
        Route::with(['spawnList.monsterSpawns.monster'])->get();
        $oldTime = (microtime(true) - $start) * 1000;

        // 新システムでのクエリ時間測定
        $start = microtime(true);
        Route::with(['monsterSpawns.monster'])->get();
        $newTime = (microtime(true) - $start) * 1000;

        $improvement = $oldTime > 0 ? round(($oldTime / $newTime), 2) : 'N/A';

        $this->table(
            ['測定項目', '旧システム', '新システム', '改善倍率'],
            [
                ['クエリ時間', round($oldTime, 2) . 'ms', round($newTime, 2) . 'ms', "{$improvement}x"],
                ['JOIN数', '3層', '2層', '1層削減'],
                ['テーブル数', '2テーブル', '1テーブル', '1テーブル削減'],
            ]
        );
    }
}