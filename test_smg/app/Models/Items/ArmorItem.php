<?php

namespace App\Models\Items;

use App\Enums\ItemCategory;

class ArmorItem extends EquippableItem
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->item_type = 'armor';
    }

    /**
     * 防具タイプを取得
     */
    public function getArmorType(): string
    {
        return match($this->category) {
            ItemCategory::HEAD_EQUIPMENT => 'helmet',
            ItemCategory::BODY_EQUIPMENT => 'body_armor',
            ItemCategory::FOOT_EQUIPMENT => 'boots',
            ItemCategory::SHIELD => 'shield',
            ItemCategory::ACCESSORY => 'accessory',
            ItemCategory::BAG => 'bag',
            default => 'unknown',
        };
    }

    /**
     * 防御力を取得
     */
    public function getDefensePower(): int
    {
        return $this->getEffectValue('defense');
    }

    /**
     * 物理ダメージ軽減率を取得
     */
    public function getPhysicalDamageReduction(): int
    {
        return $this->getEffectValue('physical_damage_reduction');
    }

    /**
     * 魔法ダメージ軽減率を取得
     */
    public function getMagicalDamageReduction(): int
    {
        return $this->getEffectValue('magical_damage_reduction');
    }

    /**
     * 特殊効果があるかチェック
     */
    public function hasSpecialEffect(): bool
    {
        $effects = $this->getEffects();
        $specialEffects = [
            'status_immunity',
            'dice_bonus',
            'extra_dice',
            'inventory_slots',
            'physical_damage_reduction',
            'magical_damage_reduction',
        ];

        foreach ($specialEffects as $effect) {
            if (isset($effects[$effect])) {
                return true;
            }
        }

        return false;
    }

    /**
     * 特殊効果の説明を取得
     */
    public function getSpecialEffectDescription(): string
    {
        $effects = $this->getEffects();
        $descriptions = [];

        if (isset($effects['status_immunity']) && $effects['status_immunity']) {
            $descriptions[] = '状態異常無効';
        }

        if (isset($effects['dice_bonus'])) {
            $descriptions[] = "移動サイコロ+{$effects['dice_bonus']}";
        }

        if (isset($effects['extra_dice'])) {
            $descriptions[] = "追加サイコロ+{$effects['extra_dice']}個";
        }

        if (isset($effects['inventory_slots'])) {
            $descriptions[] = "インベントリ拡張+{$effects['inventory_slots']}枠";
        }

        if (isset($effects['physical_damage_reduction'])) {
            $descriptions[] = "物理ダメージ{$effects['physical_damage_reduction']}%軽減";
        }

        if (isset($effects['magical_damage_reduction'])) {
            $descriptions[] = "魔法ダメージ{$effects['magical_damage_reduction']}%軽減";
        }

        return implode('、', $descriptions);
    }

    public function getItemInfo(): array
    {
        $info = parent::getItemInfo();
        
        return array_merge($info, [
            'armor_type' => $this->getArmorType(),
            'defense_power' => $this->getDefensePower(),
            'physical_damage_reduction' => $this->getPhysicalDamageReduction(),
            'magical_damage_reduction' => $this->getMagicalDamageReduction(),
            'has_special_effect' => $this->hasSpecialEffect(),
            'special_effect_description' => $this->getSpecialEffectDescription(),
        ]);
    }

    /**
     * 防具のサンプルデータ
     */
    public static function getSampleArmor(): array
    {
        return [
            // 胴体装備
            [
                'name' => '革の鎧',
                'description' => '防御力+3の軽装鎧',
                'category' => ItemCategory::BODY_EQUIPMENT,
                'rarity' => 1,
                'value' => 80,
                'effects' => ['defense' => 3],
                'max_durability' => 80,
                'item_type' => 'armor',
            ],
            [
                'name' => '鋼の鎧',
                'description' => '防御力+8の頑丈な鎧',
                'category' => ItemCategory::BODY_EQUIPMENT,
                'rarity' => 2,
                'value' => 200,
                'effects' => ['defense' => 8],
                'max_durability' => 120,
                'required_level' => 3,
                'item_type' => 'armor',
            ],
            [
                'name' => 'ドラゴンスケイル',
                'description' => '防御力+15、HP+20のドラゴンの鱗の鎧',
                'category' => ItemCategory::BODY_EQUIPMENT,
                'rarity' => 4,
                'value' => 800,
                'effects' => ['defense' => 15, 'hp' => 20],
                'max_durability' => 200,
                'required_level' => 10,
                'item_type' => 'armor',
            ],
            [
                'name' => '影の外套',
                'description' => '防御力+6、回避+12の闇の外套',
                'category' => ItemCategory::BODY_EQUIPMENT,
                'rarity' => 3,
                'value' => 350,
                'effects' => ['defense' => 6, 'evasion' => 12],
                'max_durability' => 90,
                'required_level' => 7,
                'item_type' => 'armor',
            ],

            // 盾
            [
                'name' => '木の盾',
                'description' => '防御力+2の基本的な盾',
                'category' => ItemCategory::SHIELD,
                'rarity' => 1,
                'value' => 60,
                'effects' => ['defense' => 2],
                'max_durability' => 90,
                'item_type' => 'armor',
            ],
            [
                'name' => '鉄の盾',
                'description' => '防御力+5の基本的な盾',
                'category' => ItemCategory::SHIELD,
                'rarity' => 1,
                'value' => 120,
                'effects' => ['defense' => 5],
                'max_durability' => 120,
                'item_type' => 'armor',
            ],
            [
                'name' => '魔法の盾',
                'description' => '防御力+8、MP+15の魔法の盾',
                'category' => ItemCategory::SHIELD,
                'rarity' => 3,
                'value' => 400,
                'effects' => ['defense' => 8, 'mp' => 15],
                'max_durability' => 150,
                'required_level' => 5,
                'item_type' => 'armor',
            ],

            // 頭装備
            [
                'name' => '鉄の兜',
                'description' => '防御力+3の基本的な兜',
                'category' => ItemCategory::HEAD_EQUIPMENT,
                'rarity' => 1,
                'value' => 100,
                'effects' => ['defense' => 3],
                'max_durability' => 80,
                'item_type' => 'armor',
            ],
            [
                'name' => '知恵の兜',
                'description' => '防御力+4、MP+10の賢者の兜',
                'category' => ItemCategory::HEAD_EQUIPMENT,
                'rarity' => 2,
                'value' => 250,
                'effects' => ['defense' => 4, 'mp' => 10],
                'max_durability' => 100,
                'required_level' => 4,
                'item_type' => 'armor',
            ],

            // 足装備
            [
                'name' => '革のブーツ',
                'description' => '素早さ+3の軽い靴',
                'category' => ItemCategory::FOOT_EQUIPMENT,
                'rarity' => 1,
                'value' => 60,
                'effects' => ['agility' => 3],
                'max_durability' => 70,
                'item_type' => 'armor',
            ],
            [
                'name' => '疾風のブーツ',
                'description' => '素早さ+8、移動サイコロ+1の風の靴',
                'category' => ItemCategory::FOOT_EQUIPMENT,
                'rarity' => 3,
                'value' => 300,
                'effects' => ['agility' => 8, 'extra_dice' => 1],
                'max_durability' => 100,
                'required_level' => 6,
                'item_type' => 'armor',
            ],

            // アクセサリー
            [
                'name' => 'パワーリング',
                'description' => '攻撃力+4を与える指輪',
                'category' => ItemCategory::ACCESSORY,
                'rarity' => 2,
                'value' => 150,
                'effects' => ['attack' => 4],
                'max_durability' => 60,
                'item_type' => 'armor',
            ],
            [
                'name' => '状態異常耐性の指輪',
                'description' => 'すべての状態異常を無効化する指輪',
                'category' => ItemCategory::ACCESSORY,
                'rarity' => 4,
                'value' => 500,
                'effects' => ['status_immunity' => true],
                'max_durability' => 80,
                'required_level' => 8,
                'item_type' => 'armor',
            ],
            [
                'name' => '幸運のお守り',
                'description' => '移動時のサイコロの目+2のお守り',
                'category' => ItemCategory::ACCESSORY,
                'rarity' => 3,
                'value' => 250,
                'effects' => ['dice_bonus' => 2],
                'max_durability' => 50,
                'required_level' => 5,
                'item_type' => 'armor',
            ],

            // 鞄
            [
                'name' => '小さな袋',
                'description' => 'インベントリを2枠拡張する袋',
                'category' => ItemCategory::BAG,
                'rarity' => 2,
                'value' => 200,
                'effects' => ['inventory_slots' => 2],
                'max_durability' => 120,
                'item_type' => 'armor',
            ],
        ];
    }
}