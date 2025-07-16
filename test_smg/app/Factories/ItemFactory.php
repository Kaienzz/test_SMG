<?php

namespace App\Factories;

use App\Contracts\ItemInterface;
use App\Models\Items\AbstractItem;
use App\Models\Items\ConsumableItem;
use App\Models\Items\WeaponItem;
use App\Models\Items\ArmorItem;
use App\Models\Items\MaterialItem;
use App\Enums\ItemCategory;
use InvalidArgumentException;

class ItemFactory
{
    /**
     * アイテムタイプとクラスのマッピング
     */
    private static array $itemTypeMap = [
        'consumable' => ConsumableItem::class,
        'weapon' => WeaponItem::class,
        'armor' => ArmorItem::class,
        'material' => MaterialItem::class,
    ];

    /**
     * カテゴリからアイテムタイプを判定するマッピング
     */
    private static array $categoryToTypeMap = [
        'potion' => 'consumable',
        'weapon' => 'weapon',
        'head_equipment' => 'armor',
        'body_equipment' => 'armor',
        'foot_equipment' => 'armor',
        'shield' => 'armor',
        'accessory' => 'armor',
        'bag' => 'armor',
        'material' => 'material',
    ];

    /**
     * データからアイテムインスタンスを作成
     */
    public static function create(array $data): ItemInterface
    {
        $itemType = self::determineItemType($data);
        $className = self::getItemClass($itemType);
        
        return new $className($data);
    }

    /**
     * アイテムタイプからアイテムインスタンスを作成
     */
    public static function createByType(string $itemType, array $data): ItemInterface
    {
        $className = self::getItemClass($itemType);
        
        return new $className($data);
    }

    /**
     * カテゴリからアイテムインスタンスを作成
     */
    public static function createByCategory(ItemCategory $category, array $data): ItemInterface
    {
        $data['category'] = $category;
        $itemType = self::getItemTypeFromCategory($category);
        
        return self::createByType($itemType, $data);
    }

    /**
     * 既存のItemモデルを新しいアイテムクラスに変換
     */
    public static function fromExistingItem($item): ItemInterface
    {
        $data = $item->toArray();
        
        // 必要に応じてデータを調整
        if (isset($data['category']) && is_string($data['category'])) {
            $data['category'] = ItemCategory::from($data['category']);
        }
        
        return self::create($data);
    }

    /**
     * サンプルアイテムを一括作成
     */
    public static function createSampleItems(): array
    {
        $items = [];
        
        // 消費アイテム
        foreach (ConsumableItem::getSampleConsumables() as $data) {
            $items[] = self::createByType('consumable', $data);
        }
        
        // 武器
        foreach (WeaponItem::getSampleWeapons() as $data) {
            $items[] = self::createByType('weapon', $data);
        }
        
        // 防具
        foreach (ArmorItem::getSampleArmor() as $data) {
            $items[] = self::createByType('armor', $data);
        }
        
        // 素材
        foreach (MaterialItem::getSampleMaterials() as $data) {
            $items[] = self::createByType('material', $data);
        }
        
        return $items;
    }

    /**
     * 名前でサンプルアイテムを検索・作成
     */
    public static function createSampleItemByName(string $name): ?ItemInterface
    {
        $allSamples = self::createSampleItems();
        
        foreach ($allSamples as $item) {
            if ($item->getName() === $name) {
                return $item;
            }
        }
        
        return null;
    }

    /**
     * レアリティでサンプルアイテムをフィルタリング
     */
    public static function createSampleItemsByRarity(int $rarity): array
    {
        $allSamples = self::createSampleItems();
        
        return array_filter($allSamples, function($item) use ($rarity) {
            return $item->getRarity() === $rarity;
        });
    }

    /**
     * カテゴリでサンプルアイテムをフィルタリング
     */
    public static function createSampleItemsByCategory(ItemCategory $category): array
    {
        $allSamples = self::createSampleItems();
        
        return array_filter($allSamples, function($item) use ($category) {
            return $item->getCategory() === $category;
        });
    }

    /**
     * データからアイテムタイプを判定
     */
    private static function determineItemType(array $data): string
    {
        // 明示的にitem_typeが指定されている場合
        if (isset($data['item_type'])) {
            return $data['item_type'];
        }
        
        // カテゴリから判定
        if (isset($data['category'])) {
            $category = $data['category'];
            if ($category instanceof ItemCategory) {
                return self::getItemTypeFromCategory($category);
            }
            if (is_string($category)) {
                return self::getItemTypeFromCategory(ItemCategory::from($category));
            }
        }
        
        // デフォルトとして素材を返す
        return 'material';
    }

    /**
     * カテゴリからアイテムタイプを取得
     */
    private static function getItemTypeFromCategory(ItemCategory $category): string
    {
        return self::$categoryToTypeMap[$category->value] ?? 'material';
    }

    /**
     * アイテムタイプからクラス名を取得
     */
    private static function getItemClass(string $itemType): string
    {
        if (!isset(self::$itemTypeMap[$itemType])) {
            throw new InvalidArgumentException("Unknown item type: {$itemType}");
        }
        
        return self::$itemTypeMap[$itemType];
    }

    /**
     * 利用可能なアイテムタイプ一覧を取得
     */
    public static function getAvailableItemTypes(): array
    {
        return array_keys(self::$itemTypeMap);
    }

    /**
     * アイテムタイプのクラス情報を取得
     */
    public static function getItemTypeInfo(): array
    {
        $info = [];
        
        foreach (self::$itemTypeMap as $type => $className) {
            $info[$type] = [
                'type' => $type,
                'class' => $className,
                'categories' => self::getCategoriesForType($type),
            ];
        }
        
        return $info;
    }

    /**
     * アイテムタイプに対応するカテゴリ一覧を取得
     */
    private static function getCategoriesForType(string $type): array
    {
        $categories = [];
        
        foreach (self::$categoryToTypeMap as $category => $itemType) {
            if ($itemType === $type) {
                $categories[] = $category;
            }
        }
        
        return $categories;
    }

    /**
     * ランダムなアイテムを生成
     */
    public static function createRandomItem(?ItemCategory $category = null, ?int $rarity = null): ItemInterface
    {
        $samples = self::createSampleItems();
        
        if ($category) {
            $samples = array_filter($samples, function($item) use ($category) {
                return $item->getCategory() === $category;
            });
        }
        
        if ($rarity) {
            $samples = array_filter($samples, function($item) use ($rarity) {
                return $item->getRarity() === $rarity;
            });
        }
        
        if (empty($samples)) {
            throw new InvalidArgumentException('No matching sample items found');
        }
        
        return $samples[array_rand($samples)];
    }

    /**
     * アイテムをIDから復元（データベース用）
     */
    public static function createFromDatabase(int $id): ?ItemInterface
    {
        // ここではサンプルとして、実際のプロジェクトでは
        // データベースからデータを取得してItemを復元する
        
        // 仮実装：サンプルアイテムからIDで検索
        $samples = self::createSampleItems();
        
        foreach ($samples as $item) {
            if (isset($item->id) && $item->id === $id) {
                return $item;
            }
        }
        
        return null;
    }
}