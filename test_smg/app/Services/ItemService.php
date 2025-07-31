<?php

namespace App\Services;

use App\Contracts\ItemInterface;
use App\Factories\ItemFactory;
use App\Enums\ItemCategory;
use App\Models\Item; // 既存のItemモデル

class ItemService
{
    /**
     * アイテムの使用
     */
    public static function useItem(ItemInterface $item, array $target): array
    {
        if (!$item->isUsable()) {
            return [
                'success' => false,
                'message' => 'このアイテムは使用できません。',
            ];
        }

        return $item->use($target);
    }

    /**
     * アイテムの装備
     */
    public static function equipItem(ItemInterface $item, array $character, string $slot): array
    {
        if (!$item->isEquippable()) {
            return [
                'success' => false,
                'message' => 'このアイテムは装備できません。',
            ];
        }

        // EquippableInterfaceのチェック
        if (!method_exists($item, 'canEquipBy')) {
            return [
                'success' => false,
                'message' => '装備処理でエラーが発生しました。',
            ];
        }

        if (!$item->canEquipBy($character)) {
            return [
                'success' => false,
                'message' => 'このアイテムを装備する条件を満たしていません。',
            ];
        }

        return [
            'success' => true,
            'message' => "{$item->getName()}を装備しました。",
            'item' => $item,
        ];
    }

    /**
     * アイテム情報の詳細取得
     */
    public static function getDetailedItemInfo(ItemInterface $item): array
    {
        $baseInfo = $item->getItemInfo();
        
        // アイテムタイプ別の追加情報
        $additionalInfo = [];
        
        if ($item->isUsable()) {
            $additionalInfo['usage_info'] = [
                'can_use' => true,
                'usage_description' => self::getUsageDescription($item),
            ];
        }
        
        if ($item->isEquippable()) {
            $additionalInfo['equipment_info'] = [
                'can_equip' => true,
                'equipment_description' => self::getEquipmentDescription($item),
            ];
        }
        
        return array_merge($baseInfo, $additionalInfo);
    }

    /**
     * アイテムの価値計算
     */
    public static function calculateItemValue(ItemInterface $item): array
    {
        $baseValue = $item->getValue();
        
        return [
            'base_value' => $baseValue,
            'adjusted_value' => $baseValue,
            'sell_value' => (int) round($baseValue * 0.8), // 売却時は80%
        ];
    }

    /**
     * アイテムのバッチ処理
     */
    public static function processItemBatch(array $items, string $action, array $params = []): array
    {
        $results = [];
        
        foreach ($items as $item) {
            if (!$item instanceof ItemInterface) {
                $results[] = [
                    'success' => false,
                    'message' => '不正なアイテムです。',
                ];
                continue;
            }
            
            $result = match($action) {
                'use' => self::useItem($item, $params['target'] ?? []),
                'equip' => self::equipItem($item, $params['character'] ?? [], $params['slot'] ?? ''),
                'info' => ['success' => true, 'info' => self::getDetailedItemInfo($item)],
                'value' => ['success' => true, 'value' => self::calculateItemValue($item)],
                default => ['success' => false, 'message' => '不明なアクションです。'],
            };
            
            $results[] = $result;
        }
        
        return $results;
    }

    /**
     * 既存のItemモデルを新しいシステムに変換
     */
    public static function convertLegacyItem(Item $legacyItem): ItemInterface
    {
        return ItemFactory::fromExistingItem($legacyItem);
    }

    /**
     * アイテムの互換性チェック
     */
    public static function checkCompatibility(ItemInterface $item1, ItemInterface $item2): array
    {
        $compatibility = [
            'can_stack' => false,
            'same_category' => false,
            'same_type' => false,
        ];
        
        // スタック可能性チェック
        if ($item1->getName() === $item2->getName() && 
            $item1->canStack() && $item2->canStack()) {
            $compatibility['can_stack'] = true;
        }
        
        // カテゴリ一致チェック
        if ($item1->getCategory() === $item2->getCategory()) {
            $compatibility['same_category'] = true;
        }
        
        // タイプ一致チェック（item_typeが存在する場合）
        if (method_exists($item1, 'item_type') && method_exists($item2, 'item_type')) {
            if ($item1->item_type === $item2->item_type) {
                $compatibility['same_type'] = true;
            }
        }
        
        
        return $compatibility;
    }

    /**
     * アイテムのフィルタリング
     */
    public static function filterItems(array $items, array $filters): array
    {
        return array_filter($items, function($item) use ($filters) {
            if (!$item instanceof ItemInterface) {
                return false;
            }
            
            // カテゴリフィルタ
            if (isset($filters['category']) && $item->getCategory() !== $filters['category']) {
                return false;
            }
            
            // レアリティフィルタ
            if (isset($filters['rarity']) && $item->getRarity() !== $filters['rarity']) {
                return false;
            }
            
            // 使用可能フィルタ
            if (isset($filters['usable']) && $item->isUsable() !== $filters['usable']) {
                return false;
            }
            
            // 装備可能フィルタ
            if (isset($filters['equippable']) && $item->isEquippable() !== $filters['equippable']) {
                return false;
            }
            
            // 名前フィルタ（部分一致）
            if (isset($filters['name']) && stripos($item->getName(), $filters['name']) === false) {
                return false;
            }
            
            return true;
        });
    }

    private static function getUsageDescription(ItemInterface $item): string
    {
        if (method_exists($item, 'getEffectDescription')) {
            return $item->getEffectDescription();
        }
        
        return 'このアイテムは使用できます。';
    }

    private static function getEquipmentDescription(ItemInterface $item): string
    {
        if (method_exists($item, 'getEquipmentSlot')) {
            $slot = $item->getEquipmentSlot();
            $slotName = match($slot) {
                'weapon' => '武器',
                'helmet' => '頭',
                'body_armor' => '胴体',
                'boots' => '足',
                'shield' => '盾',
                'accessory' => '装飾品',
                'bag' => '鞄',
                default => '不明',
            };
            
            return "{$slotName}に装備できます。";
        }
        
        return 'このアイテムは装備できます。';
    }
}