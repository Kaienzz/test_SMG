<?php

namespace App\Services\StandardItem;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\StandardItem;

/**
 * 標準アイテム管理サービス（SQLite対応）
 * 
 * SQLiteデータベースのstandard_itemsテーブルを管理
 * JSONファイルをフォールバックとして使用
 */
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
     * 標準アイテムデータを取得（SQLite優先、JSONフォールバック）
     */
    public function getStandardItems(): array
    {
        try {
            // SQLiteから取得を試行
            $items = StandardItem::where('is_standard', true)
                                ->orderBy('category')
                                ->orderBy('id')
                                ->get()
                                ->keyBy('id')
                                ->map(function ($item) {
                                    return $item->toArray();
                                })
                                ->toArray();
            
            if (!empty($items)) {
                Log::debug('Standard items loaded from SQLite successfully', [
                    'count' => count($items)
                ]);
                return $items;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load standard items from SQLite, falling back to JSON', [
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSONフォールバック
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
     * IDで標準アイテムを検索（SQLite優先）
     */
    public function findById(string $id): ?array
    {
        try {
            $item = StandardItem::find($id);
            if ($item) {
                return $item->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to find standard item by ID in SQLite, falling back to JSON', [
                'item_id' => $id,
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSONフォールバック
        $items = $this->getStandardItems();
        
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * 名前で標準アイテムを検索（SQLite優先、大文字小文字区別なし）
     */
    public function findByName(string $name): ?array
    {
        try {
            $item = StandardItem::whereRaw('LOWER(name) = LOWER(?)', [$name])->first();
            if ($item) {
                return $item->toArray();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to find standard item by name in SQLite, falling back to JSON', [
                'item_name' => $name,
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSONフォールバック
        $items = $this->getStandardItems();
        
        foreach ($items as $item) {
            if (strcasecmp($item['name'], $name) === 0) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * カテゴリで標準アイテムをフィルタ（SQLite優先）
     */
    public function getByCategory(string $category): array
    {
        try {
            $items = StandardItem::byCategory($category)
                                ->where('is_standard', true)
                                ->orderBy('id')
                                ->get()
                                ->map(function ($item) {
                                    return $item->toArray();
                                })
                                ->toArray();
            
            if (!empty($items)) {
                return $items;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get standard items by category from SQLite, falling back to JSON', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSONフォールバック
        $items = $this->getStandardItems();
        
        return array_filter($items, function($item) use ($category) {
            return $item['category'] === $category;
        });
    }

    
    /**
     * 装備可能アイテムを取得（SQLite優先）
     */
    public function getEquippableItems(): array
    {
        try {
            $items = StandardItem::equippable()
                                ->where('is_standard', true)
                                ->orderBy('category')
                                ->orderBy('id')
                                ->get()
                                ->map(function ($item) {
                                    return $item->toArray();
                                })
                                ->toArray();
            
            if (!empty($items)) {
                return $items;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get equippable items from SQLite, falling back to JSON', [
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSON経由でフィルタ
        $items = $this->getStandardItems();
        return array_filter($items, function($item) {
            return $item['is_equippable'] ?? false;
        });
    }

    /**
     * 使用可能アイテムを取得（SQLite優先）
     */
    public function getUsableItems(): array
    {
        try {
            $items = StandardItem::usable()
                                ->where('is_standard', true)
                                ->orderBy('category')
                                ->orderBy('id')
                                ->get()
                                ->map(function ($item) {
                                    return $item->toArray();
                                })
                                ->toArray();
            
            if (!empty($items)) {
                return $items;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get usable items from SQLite, falling back to JSON', [
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSON経由でフィルタ
        $items = $this->getStandardItems();
        return array_filter($items, function($item) {
            return $item['is_usable'] ?? false;
        });
    }

    /**
     * 武器タイプでアイテムを取得（SQLite優先）
     */
    public function getByWeaponType(string $weaponType): array
    {
        try {
            $items = StandardItem::byWeaponType($weaponType)
                                ->where('is_standard', true)
                                ->orderBy('id')
                                ->get()
                                ->map(function ($item) {
                                    return $item->toArray();
                                })
                                ->toArray();
            
            if (!empty($items)) {
                return $items;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get items by weapon type from SQLite, falling back to JSON', [
                'weapon_type' => $weaponType,
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSON経由でフィルタ
        $items = $this->getStandardItems();
        return array_filter($items, function($item) use ($weaponType) {
            return ($item['weapon_type'] ?? '') === $weaponType;
        });
    }

    /**
     * キャッシュをクリア（SQLite版では不要だが互換性のため保持）
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        // SQLiteではEloquentが自動的にキャッシュを管理するため、特に処理は不要
        Log::debug('Cache clear requested for StandardItemService (SQLite version)');
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
     * 統計情報を取得（SQLite優先）
     */
    public function getStatistics(): array
    {
        try {
            // SQLiteから効率的に統計情報を取得
            $totalItems = StandardItem::where('is_standard', true)->count();
            
            // カテゴリ別統計
            $categories = StandardItem::where('is_standard', true)
                                     ->selectRaw('category, COUNT(*) as count')
                                     ->groupBy('category')
                                     ->pluck('count', 'category')
                                     ->toArray();
            
            // 装備・使用可能アイテム統計
            $equippableCount = StandardItem::equippable()->where('is_standard', true)->count();
            $usableCount = StandardItem::usable()->where('is_standard', true)->count();
            
            // 武器タイプ統計
            $weaponTypes = StandardItem::where('is_standard', true)
                                      ->whereNotNull('weapon_type')
                                      ->where('weapon_type', '!=', '')
                                      ->selectRaw('weapon_type, COUNT(*) as count')
                                      ->groupBy('weapon_type')
                                      ->pluck('count', 'weapon_type')
                                      ->toArray();
            
            // 平均価値
            $avgValue = StandardItem::where('is_standard', true)->avg('value') ?? 0;
            
            return [
                'total_items' => $totalItems,
                'categories' => $categories,
                'equippable_count' => $equippableCount,
                'usable_count' => $usableCount,
                'weapon_types' => $weaponTypes,
                'by_category' => collect($categories),
                'avg_value' => $avgValue,
            ];
            
        } catch (\Exception $e) {
            Log::warning('Failed to get statistics from SQLite, falling back to JSON', [
                'error' => $e->getMessage()
            ]);
        }
        
        // SQLite失敗時はJSONフォールバック
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