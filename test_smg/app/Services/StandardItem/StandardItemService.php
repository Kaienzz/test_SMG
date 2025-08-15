<?php

namespace App\Services\StandardItem;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StandardItemService
{
    private const CACHE_KEY = 'standard_items_data';
    private const CACHE_DURATION = 3600; // 1時間
    private string $jsonFilePath;
    
    private StandardItemValidator $validator;
    
    public function __construct()
    {
        $this->validator = new StandardItemValidator();
        $this->jsonFilePath = config('items.json_file_path', 'data/standard_items.json');
    }
    
    /**
     * 標準アイテムデータを取得（DummyDataService::getStandardItems()互換）
     */
    public function getStandardItems(): array
    {
        $data = $this->loadStandardItemsData();
        
        if (!$data || !isset($data['items'])) {
            Log::warning('Standard items data is invalid, falling back to empty array');
            return [];
        }
        
        return $data['items'];
    }
    
    /**
     * 標準アイテムデータをフル形式で取得（メタデータ含む）
     */
    public function getFullData(): array
    {
        return $this->loadStandardItemsData();
    }
    
    /**
     * IDで標準アイテムを検索
     */
    public function findById(string $id): ?array
    {
        $items = $this->getStandardItems();
        
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * 名前で標準アイテムを検索（大文字小文字区別なし）
     */
    public function findByName(string $name): ?array
    {
        $items = $this->getStandardItems();
        
        foreach ($items as $item) {
            if (strcasecmp($item['name'], $name) === 0) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * カテゴリで標準アイテムをフィルタ
     */
    public function getByCategory(string $category): array
    {
        $items = $this->getStandardItems();
        
        return array_filter($items, function($item) use ($category) {
            return $item['category'] === $category;
        });
    }

    
    /**
     * キャッシュをクリア
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
    
    /**
     * JSONファイルを再読み込みしてキャッシュを更新
     */
    public function reloadData(): array
    {
        $this->clearCache();
        return $this->loadStandardItemsData();
    }
    
    /**
     * データ整合性チェック
     */
    public function validateData(): array
    {
        try {
            $data = $this->loadDataFromFile();
            $this->validator->validateAndThrow($data);
            
            return [
                'valid' => true,
                'errors' => [],
                'items_count' => count($data['items'] ?? []),
                'schema_version' => $data['schema_version'] ?? 'unknown'
            ];
        } catch (StandardItemValidationException $e) {
            return [
                'valid' => false,
                'errors' => $e->getErrors(),
                'items_count' => 0,
                'schema_version' => 'unknown'
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['File read error: ' . $e->getMessage()],
                'items_count' => 0,
                'schema_version' => 'unknown'
            ];
        }
    }
    
    /**
     * 標準アイテムデータを読み込み（キャッシュ対応）
     */
    private function loadStandardItemsData(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_DURATION, function () {
            try {
                return $this->loadDataFromFile();
            } catch (\Exception $e) {
                Log::error('Failed to load standard items from JSON', [
                    'error' => $e->getMessage(),
                    'file' => $this->jsonFilePath
                ]);
                
                // JSONファイル読み込み失敗時は空のデータを返す
                return [
                    'schema_version' => '1.0',
                    'items' => [],
                    'last_updated' => now()->toDateString(),
                    'description' => 'Fallback empty data due to file read error'
                ];
            }
        });
    }
    
    /**
     * JSONファイルから直接データを読み込み
     */
    private function loadDataFromFile(): array
    {
        if (!Storage::exists($this->jsonFilePath)) {
            throw new \RuntimeException("Standard items JSON file not found: " . $this->jsonFilePath);
        }
        
        $jsonContent = Storage::get($this->jsonFilePath);
        
        if ($jsonContent === false) {
            throw new \RuntimeException("Failed to read standard items JSON file");
        }
        
        $data = json_decode($jsonContent, true);
        
        if ($data === null) {
            throw new \RuntimeException("Invalid JSON in standard items file: " . json_last_error_msg());
        }
        
        // バリデーション実行
        $this->validator->validateAndThrow($data);
        
        return $data;
    }
    
    /**
     * 統計情報を取得
     */
    public function getStatistics(): array
    {
        $items = $this->getStandardItems();
        
        $stats = [
            'total_items' => count($items),
            'categories' => [],
            'equippable_count' => 0,
            'usable_count' => 0,
            'weapon_types' => [],
        ];
        
        foreach ($items as $item) {
            // カテゴリ統計
            $category = $item['category'];
            $stats['categories'][$category] = ($stats['categories'][$category] ?? 0) + 1;
            
            // 装備可能アイテム数
            if ($item['is_equippable']) {
                $stats['equippable_count']++;
            }
            
            // 使用可能アイテム数
            if ($item['is_usable']) {
                $stats['usable_count']++;
            }
            
            // 武器タイプ統計
            if (!empty($item['weapon_type'])) {
                $weaponType = $item['weapon_type'];
                $stats['weapon_types'][$weaponType] = ($stats['weapon_types'][$weaponType] ?? 0) + 1;
            }
        }
        
        // 統計情報を整理
        $stats['by_category'] = collect($stats['categories']);
        $stats['avg_value'] = collect($items)->avg('value');
        
        return $stats;
    }
}