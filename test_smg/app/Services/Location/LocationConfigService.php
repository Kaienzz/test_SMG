<?php

namespace App\Services\Location;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ロケーション設定ファイル管理サービス
 * 
 * JSON設定ファイルの読み書き、検証、バックアップを担当
 */
class LocationConfigService
{
    private string $configPath;
    private string $backupPath;
    private string $cacheKey = 'location_config';
    private int $cacheDuration = 3600; // 1時間

    public function __construct()
    {
        $this->configPath = config_path('locations/locations.json');
        $this->backupPath = config_path('locations/backups');
        
        // バックアップディレクトリの確保
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * ロケーション設定を読み込み
     *
     * @param bool $useCache キャッシュを使用するか
     * @return array
     * @throws \Exception
     */
    public function loadConfig(bool $useCache = true): array
    {
        if ($useCache) {
            $cached = Cache::get($this->cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        if (!File::exists($this->configPath)) {
            throw new \Exception("Location configuration file not found: {$this->configPath}");
        }

        $content = File::get($this->configPath);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in location configuration: " . json_last_error_msg());
        }

        // 古いフォーマットの場合は自動移行
        if ($this->isLegacyFormat($config)) {
            $config = $this->migrateLegacyFormat($config);
            // 移行後の設定を保存
            $this->saveConfig($config, true);
            // キャッシュをクリア（新しい設定でキャッシュし直すため）
            Cache::forget($this->cacheKey);
        }

        $this->validateConfig($config);

        // キャッシュに保存
        if ($useCache) {
            Cache::put($this->cacheKey, $config, $this->cacheDuration);
        }

        return $config;
    }

    /**
     * ロケーション設定を保存
     *
     * @param array $config
     * @param bool $createBackup バックアップを作成するか
     * @return bool
     * @throws \Exception
     */
    public function saveConfig(array $config, bool $createBackup = true): bool
    {
        // 設定の検証
        $this->validateConfig($config);

        // バックアップ作成
        if ($createBackup && File::exists($this->configPath)) {
            $this->createBackup();
        }

        // 更新日時を設定
        $config['last_updated'] = now()->toISOString();

        // JSON形式で保存
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to encode configuration to JSON: " . json_last_error_msg());
        }

        $success = File::put($this->configPath, $json);
        
        if ($success) {
            // キャッシュをクリア
            Cache::forget($this->cacheKey);
            Log::info('Location configuration updated', ['file' => $this->configPath]);
            return true;
        }

        return false;
    }

    /**
     * 設定の検証
     *
     * @param array $config
     * @throws \Exception
     */
    private function validateConfig(array $config): void
    {
        // 古いフォーマットの場合はエラー（loadConfigで移行されているはず）
        if ($this->isLegacyFormat($config)) {
            throw new \Exception("Legacy format detected but not migrated. This should not happen.");
        }

        // 必須フィールドの確認（新フォーマット）
        $requiredFields = ['version', 'pathways', 'towns'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                throw new \Exception("Missing required field in configuration: {$field}");
            }
        }

        // pathways設定の検証
        if (!is_array($config['pathways'])) {
            throw new \Exception("Pathways configuration must be an array");
        }

        foreach ($config['pathways'] as $pathwayId => $pathwayData) {
            $this->validatePathway($pathwayId, $pathwayData);
        }

        // 町設定の検証
        if (!is_array($config['towns'])) {
            throw new \Exception("Towns configuration must be an array");
        }

        foreach ($config['towns'] as $townId => $townData) {
            if (!isset($townData['name'])) {
                throw new \Exception("Town {$townId} missing required field: name");
            }
        }
    }

    /**
     * 個別pathwayの検証
     *
     * @param string $pathwayId
     * @param array $pathwayData
     * @throws \Exception
     */
    private function validatePathway(string $pathwayId, array $pathwayData): void
    {
        // 必須フィールド
        if (!isset($pathwayData['name'])) {
            throw new \Exception("Pathway {$pathwayId} missing required field: name");
        }

        if (!isset($pathwayData['category'])) {
            throw new \Exception("Pathway {$pathwayId} missing required field: category");
        }

        // カテゴリーの検証
        $validCategories = ['road', 'dungeon'];
        if (!in_array($pathwayData['category'], $validCategories)) {
            throw new \Exception("Pathway {$pathwayId} has invalid category: {$pathwayData['category']}");
        }

        // ダンジョン固有フィールドの検証
        if ($pathwayData['category'] === 'dungeon') {
            $this->validateDungeonSpecificFields($pathwayId, $pathwayData);
        }

        // 数値フィールドの検証
        if (isset($pathwayData['length']) && (!is_numeric($pathwayData['length']) || $pathwayData['length'] < 0)) {
            throw new \Exception("Pathway {$pathwayId} has invalid length value");
        }

        if (isset($pathwayData['encounter_rate']) && (!is_numeric($pathwayData['encounter_rate']) || $pathwayData['encounter_rate'] < 0 || $pathwayData['encounter_rate'] > 1)) {
            throw new \Exception("Pathway {$pathwayId} has invalid encounter_rate value");
        }
    }

    /**
     * ダンジョン固有フィールドの検証
     *
     * @param string $pathwayId
     * @param array $pathwayData
     * @throws \Exception
     */
    private function validateDungeonSpecificFields(string $pathwayId, array $pathwayData): void
    {
        // ダンジョンタイプの検証
        if (isset($pathwayData['dungeon_type'])) {
            $validTypes = ['cave', 'ruins', 'tower', 'underground'];
            if (!in_array($pathwayData['dungeon_type'], $validTypes)) {
                throw new \Exception("Pathway {$pathwayId} has invalid dungeon_type: {$pathwayData['dungeon_type']}");
            }
        }

        // レベル制限の検証
        if (isset($pathwayData['min_level']) && (!is_numeric($pathwayData['min_level']) || $pathwayData['min_level'] < 1)) {
            throw new \Exception("Pathway {$pathwayId} has invalid min_level value");
        }

        if (isset($pathwayData['max_level']) && (!is_numeric($pathwayData['max_level']) || $pathwayData['max_level'] < 1)) {
            throw new \Exception("Pathway {$pathwayId} has invalid max_level value");
        }

        // フロア数の検証
        if (isset($pathwayData['floors']) && (!is_numeric($pathwayData['floors']) || $pathwayData['floors'] < 1)) {
            throw new \Exception("Pathway {$pathwayId} has invalid floors value");
        }
    }

    /**
     * バックアップを作成
     *
     * @return string バックアップファイルパス
     */
    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFileName = "locations_backup_{$timestamp}.json";
        $backupFilePath = $this->backupPath . '/' . $backupFileName;

        File::copy($this->configPath, $backupFilePath);
        
        Log::info('Location configuration backup created', ['backup_file' => $backupFilePath]);
        
        return $backupFilePath;
    }

    /**
     * バックアップから復元
     *
     * @param string $backupFilePath
     * @return bool
     * @throws \Exception
     */
    public function restoreFromBackup(string $backupFilePath): bool
    {
        if (!File::exists($backupFilePath)) {
            throw new \Exception("Backup file not found: {$backupFilePath}");
        }

        // バックアップファイルの内容を検証
        $content = File::get($backupFilePath);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in backup file: " . json_last_error_msg());
        }

        $this->validateConfig($config);

        // 現在の設定をバックアップ
        $this->createBackup();

        // バックアップから復元
        $success = File::copy($backupFilePath, $this->configPath);
        
        if ($success) {
            Cache::forget($this->cacheKey);
            Log::info('Location configuration restored from backup', ['backup_file' => $backupFilePath]);
        }

        return $success;
    }

    /**
     * バックアップファイル一覧を取得
     *
     * @return array
     */
    public function getBackupList(): array
    {
        $files = File::files($this->backupPath);
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.json') && 
                str_starts_with($file->getFilename(), 'locations_backup_')) {
                $backups[] = [
                    'filename' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        // 更新日時の降順でソート
        usort($backups, fn($a, $b) => $b['modified'] <=> $a['modified']);

        return $backups;
    }

    /**
     * キャッシュをクリア
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * 設定ファイルが存在するか確認
     *
     * @return bool
     */
    public function configExists(): bool
    {
        return File::exists($this->configPath);
    }

    /**
     * 設定ファイルパスを取得
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    /**
     * バックアップディレクトリパスを取得
     *
     * @return string
     */
    public function getBackupPath(): string
    {
        return $this->backupPath;
    }

    /**
     * 設定データをエクスポート
     *
     * @return array
     */
    public function exportConfig(): array
    {
        return $this->loadConfig(false);
    }

    /**
     * 設定データをインポート
     *
     * @param array $config
     * @param bool $validate 検証するか
     * @return bool
     * @throws \Exception
     */
    public function importConfig(array $config, bool $validate = true): bool
    {
        if ($validate) {
            $this->validateConfig($config);
        }

        return $this->saveConfig($config, true);
    }

    /**
     * 特定の設定セクションを取得
     *
     * @param string $section
     * @return array
     */
    public function getSection(string $section): array
    {
        $config = $this->loadConfig();
        return $config[$section] ?? [];
    }

    /**
     * 特定の設定セクションを更新
     *
     * @param string $section
     * @param array $data
     * @return bool
     */
    public function updateSection(string $section, array $data): bool
    {
        $config = $this->loadConfig(false);
        $config[$section] = $data;
        return $this->saveConfig($config);
    }

    /**
     * 古いフォーマットかどうかを判定
     *
     * @param array $config
     * @return bool
     */
    private function isLegacyFormat(array $config): bool
    {
        // roads と dungeons セクションが存在し、pathways セクションが存在しない場合は古いフォーマット
        return isset($config['roads']) && isset($config['dungeons']) && !isset($config['pathways']);
    }

    /**
     * 古いフォーマットから新しいフォーマットに移行
     *
     * @param array $config
     * @return array
     */
    private function migrateLegacyFormat(array $config): array
    {
        Log::info('Migrating legacy location configuration format');

        $newConfig = [
            'version' => '2.0.0',
            'last_updated' => now()->toISOString(),
            'description' => $config['description'] ?? 'Location configuration for test_smg game',
            'pathways' => [],
            'towns' => $config['towns'] ?? [],
            'metadata' => $config['metadata'] ?? []
        ];

        // 道路データの移行
        if (isset($config['roads']) && is_array($config['roads'])) {
            foreach ($config['roads'] as $roadId => $roadData) {
                $newConfig['pathways'][$roadId] = array_merge($roadData, [
                    'category' => 'road'
                ]);
            }
        }

        // ダンジョンデータの移行
        if (isset($config['dungeons']) && is_array($config['dungeons'])) {
            foreach ($config['dungeons'] as $dungeonId => $dungeonData) {
                $migratedDungeon = array_merge($dungeonData, [
                    'category' => 'dungeon'
                ]);

                // ダンジョン固有フィールドを推測・設定
                $migratedDungeon = $this->inferDungeonFields($migratedDungeon);

                $newConfig['pathways'][$dungeonId] = $migratedDungeon;
            }
        }

        // メタデータの更新
        if (isset($newConfig['metadata'])) {
            $pathwayCount = count($newConfig['pathways']);
            $roadCount = count(array_filter($newConfig['pathways'], fn($p) => $p['category'] === 'road'));
            $dungeonCount = count(array_filter($newConfig['pathways'], fn($p) => $p['category'] === 'dungeon'));

            $newConfig['metadata'] = array_merge($newConfig['metadata'], [
                'total_pathways' => $pathwayCount,
                'total_roads' => $roadCount,
                'total_dungeons' => $dungeonCount,
                'migration_date' => now()->toDateString(),
                'schema_version' => '2.0'
            ]);
        }

        return $newConfig;
    }

    /**
     * ダンジョンの固有フィールドを推測
     *
     * @param array $dungeonData
     * @return array
     */
    private function inferDungeonFields(array $dungeonData): array
    {
        // ダンジョンタイプを名前から推測
        if (!isset($dungeonData['dungeon_type'])) {
            $name = strtolower($dungeonData['name'] ?? '');
            if (str_contains($name, '洞窟') || str_contains($name, 'cave')) {
                $dungeonData['dungeon_type'] = 'cave';
            } elseif (str_contains($name, '遺跡') || str_contains($name, 'ruins')) {
                $dungeonData['dungeon_type'] = 'ruins';
            } elseif (str_contains($name, '塔') || str_contains($name, 'tower')) {
                $dungeonData['dungeon_type'] = 'tower';
            } elseif (str_contains($name, '地下') || str_contains($name, 'underground')) {
                $dungeonData['dungeon_type'] = 'underground';
            } else {
                $dungeonData['dungeon_type'] = 'cave'; // デフォルト
            }
        }

        // フロア数を設定（未設定の場合）
        if (!isset($dungeonData['floors'])) {
            $dungeonData['floors'] = 1;
        }

        // レベル制限を難易度から推測
        if (!isset($dungeonData['min_level']) || !isset($dungeonData['max_level'])) {
            $difficulty = $dungeonData['difficulty'] ?? 'normal';
            switch ($difficulty) {
                case 'easy':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 1;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 5;
                    break;
                case 'normal':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 3;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 10;
                    break;
                case 'hard':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 8;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 20;
                    break;
                case 'extreme':
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 15;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 50;
                    break;
                default:
                    $dungeonData['min_level'] = $dungeonData['min_level'] ?? 1;
                    $dungeonData['max_level'] = $dungeonData['max_level'] ?? 10;
            }
        }

        // ボス情報を special_actions から抽出
        if (!isset($dungeonData['boss']) && isset($dungeonData['special_actions'])) {
            foreach ($dungeonData['special_actions'] as $action) {
                if (isset($action['type']) && $action['type'] === 'boss_battle' && isset($action['data']['boss'])) {
                    $dungeonData['boss'] = $action['data']['boss'];
                    break;
                }
            }
        }

        return $dungeonData;
    }

    /**
     * 新しいフォーマットで設定をロード（自動移行対応）
     *
     * @param bool $useCache
     * @return array
     * @throws \Exception
     */
    public function loadUnifiedConfig(bool $useCache = true): array
    {
        $config = $this->loadConfig($useCache);

        // 古いフォーマットの場合は移行
        if ($this->isLegacyFormat($config)) {
            $config = $this->migrateLegacyFormat($config);
            // 移行後の設定を保存
            $this->saveConfig($config, true);
        }

        return $config;
    }

    /**
     * pathways セクションのみを取得
     *
     * @param string|null $category カテゴリーフィルター ('road', 'dungeon', null=全て)
     * @return array
     */
    public function getPathways(?string $category = null): array
    {
        $config = $this->loadUnifiedConfig();
        $pathways = $config['pathways'] ?? [];

        if ($category !== null) {
            $pathways = array_filter($pathways, fn($pathway) => ($pathway['category'] ?? '') === $category);
        }

        return $pathways;
    }

    /**
     * 道路データのみを取得（後方互換性）
     *
     * @return array
     */
    public function getRoads(): array
    {
        return $this->getPathways('road');
    }

    /**
     * ダンジョンデータのみを取得（後方互換性）
     *
     * @return array
     */
    public function getDungeons(): array
    {
        return $this->getPathways('dungeon');
    }
}