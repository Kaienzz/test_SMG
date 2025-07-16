<?php

namespace App\Examples;

use App\Factories\ItemFactory;
use App\Services\ItemService;
use App\Enums\ItemCategory;
use App\Models\Items\ConsumableItem;
use App\Models\Items\WeaponItem;
use App\Models\Items\ArmorItem;
use App\Models\Items\MaterialItem;

/**
 * 新しいアイテムシステムの使用例
 */
class ItemSystemUsage
{
    /**
     * 基本的なアイテム作成例
     */
    public static function basicItemCreation()
    {
        echo "=== 基本的なアイテム作成例 ===\n";

        // ファクトリーを使用した作成
        $potion = ItemFactory::createByType('consumable', [
            'name' => '薬草',
            'description' => 'HPを20回復する薬草',
            'category' => ItemCategory::POTION,
            'rarity' => 1,
            'value' => 10,
            'effects' => ['heal_hp' => 20],
            'effect_type' => 'heal_hp',
            'effect_value' => 20,
        ]);

        echo "作成されたアイテム: {$potion->getName()}\n";
        echo "説明: {$potion->getDescription()}\n";
        echo "効果: {$potion->getEffectDescription()}\n";

        // 武器の作成
        $sword = ItemFactory::createByType('weapon', [
            'name' => '鉄の剣',
            'description' => '攻撃力+5の基本的な剣',
            'category' => ItemCategory::WEAPON,
            'rarity' => 1,
            'value' => 100,
            'effects' => ['attack' => 5],
            'weapon_type' => WeaponItem::TYPE_PHYSICAL,
        ]);

        echo "\n作成された武器: {$sword->getName()}\n";
        echo "武器タイプ: {$sword->getWeaponType()}\n";
        echo "攻撃力: {$sword->getAttackPower()}\n";
    }

    /**
     * アイテム使用例
     */
    public static function itemUsageExample()
    {
        echo "\n=== アイテム使用例 ===\n";

        $character = [
            'name' => 'テストキャラクター',
            'hp' => 50,
            'max_hp' => 100,
            'mp' => 20,
            'max_mp' => 50,
        ];

        echo "キャラクター状態: HP {$character['hp']}/{$character['max_hp']}\n";

        // 回復アイテムの使用
        $potion = ItemFactory::createSampleItemByName('薬草');
        $result = ItemService::useItem($potion, $character);

        if ($result['success']) {
            echo "アイテム使用結果: {$result['message']}\n";
            if (isset($result['target'])) {
                $character = $result['target'];
                echo "使用後のHP: {$character['hp']}/{$character['max_hp']}\n";
            }
        }
    }

    /**
     * 装備アイテム例
     */
    public static function equipmentExample()
    {
        echo "\n=== 装備アイテム例 ===\n";

        $character = [
            'name' => 'テストキャラクター',
            'level' => 5,
            'attack' => 10,
            'defense' => 8,
        ];

        $sword = ItemFactory::createSampleItemByName('ミスリルソード');
        
        echo "装備前の攻撃力: {$character['attack']}\n";
        
        $result = ItemService::equipItem($sword, $character, 'weapon');
        
        if ($result['success']) {
            echo "装備結果: {$result['message']}\n";
            
            // 装備による効果を適用
            $effects = $sword->getStatModifiers();
            if (isset($effects['attack'])) {
                $character['attack'] += $effects['attack'];
                echo "装備後の攻撃力: {$character['attack']}\n";
            }
        }
    }

    /**
     * サンプルアイテム一覧表示
     */
    public static function displaySampleItems()
    {
        echo "\n=== サンプルアイテム一覧 ===\n";

        $items = ItemFactory::createSampleItems();
        
        $groupedItems = [];
        foreach ($items as $item) {
            $type = $item->item_type ?? 'unknown';
            $groupedItems[$type][] = $item;
        }

        foreach ($groupedItems as $type => $typeItems) {
            echo "\n--- {$type} ---\n";
            foreach ($typeItems as $item) {
                echo "- {$item->getName()} (レアリティ: {$item->getRarity()})\n";
                echo "  {$item->getDescription()}\n";
            }
        }
    }

    /**
     * フィルタリング例
     */
    public static function filteringExample()
    {
        echo "\n=== フィルタリング例 ===\n";

        $allItems = ItemFactory::createSampleItems();
        
        // レアリティ3以上のアイテムをフィルタ
        $rareItems = ItemService::filterItems($allItems, ['rarity' => 3]);
        
        echo "レアリティ3のアイテム:\n";
        foreach ($rareItems as $item) {
            echo "- {$item->getName()}\n";
        }

        // 武器のみをフィルタ
        $weapons = ItemService::filterItems($allItems, ['category' => ItemCategory::WEAPON]);
        
        echo "\n武器アイテム:\n";
        foreach ($weapons as $item) {
            echo "- {$item->getName()}\n";
        }
    }

    /**
     * 互換性例（既存システムとの連携）
     */
    public static function compatibilityExample()
    {
        echo "\n=== 既存システムとの互換性例 ===\n";

        // 既存のItemモデルを作成
        $legacyItem = \App\Models\Item::findSampleItem('薬草');
        
        if ($legacyItem) {
            echo "既存アイテム: {$legacyItem->name}\n";
            
            // 新しいシステムに変換
            $newItem = $legacyItem->toNewItemSystem();
            echo "新システムでの型: " . get_class($newItem) . "\n";
            
            // 新しいシステムで使用
            $character = ['hp' => 50, 'max_hp' => 100, 'name' => 'テスト'];
            $result = $legacyItem->useWithNewSystem($character);
            
            if ($result['success']) {
                echo "使用結果: {$result['message']}\n";
            }
        }
    }

    /**
     * すべての例を実行
     */
    public static function runAllExamples()
    {
        self::basicItemCreation();
        self::itemUsageExample();
        self::equipmentExample();
        self::displaySampleItems();
        self::filteringExample();
        self::compatibilityExample();
        
        echo "\n=== すべての例が完了しました ===\n";
    }
}