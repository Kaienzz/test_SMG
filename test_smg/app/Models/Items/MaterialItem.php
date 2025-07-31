<?php

namespace App\Models\Items;

use App\Enums\ItemCategory;

class MaterialItem extends AbstractItem
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'value',
        'effects',
        'stack_limit',
        'battle_skill_id',
        'weapon_type',
        'item_type',
        'material_type',
        'material_grade',
        'crafting_uses',
    ];

    protected $casts = [
        'category' => ItemCategory::class,
        'value' => 'integer',
        'effects' => 'array',
        'stack_limit' => 'integer',
        'material_grade' => 'integer',
        'crafting_uses' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->item_type = 'material';
    }

    // ItemInterface 実装
    public function use(array $target): array
    {
        return [
            'success' => false,
            'message' => '素材は直接使用できません。クラフトに使用してください。',
        ];
    }

    /**
     * 素材の種類を取得
     */
    public function getMaterialType(): string
    {
        return $this->material_type ?? $this->determineMaterialTypeFromName();
    }

    /**
     * 素材のグレードを取得
     */
    public function getMaterialGrade(): int
    {
        return $this->material_grade ?? 1;
    }

    /**
     * クラフトでの使用用途を取得
     */
    public function getCraftingUses(): array
    {
        return $this->crafting_uses ?? $this->determineCraftingUsesFromType();
    }

    /**
     * 売却価値を計算（素材は基本価値の90%で売却可能）
     */
    public function getSellValue(): int
    {
        return (int) round($this->getValue() * 0.9);
    }


    private function determineMaterialTypeFromName(): string
    {
        $name = strtolower($this->getName());
        
        if (str_contains($name, '鉱石') || str_contains($name, '鉄') || str_contains($name, '金') || str_contains($name, '銀')) {
            return 'ore';
        }
        
        if (str_contains($name, '木材') || str_contains($name, '枝') || str_contains($name, '樹')) {
            return 'wood';
        }
        
        if (str_contains($name, '皮') || str_contains($name, '革') || str_contains($name, '毛皮')) {
            return 'leather';
        }
        
        if (str_contains($name, '布') || str_contains($name, '糸') || str_contains($name, '繊維')) {
            return 'fabric';
        }
        
        if (str_contains($name, '宝石') || str_contains($name, 'ジェム') || str_contains($name, 'クリスタル')) {
            return 'gem';
        }
        
        if (str_contains($name, '薬草') || str_contains($name, 'ハーブ') || str_contains($name, '花')) {
            return 'herb';
        }
        
        return 'misc';
    }

    private function determineCraftingUsesFromType(): array
    {
        $materialType = $this->getMaterialType();
        
        return match($materialType) {
            'ore' => ['weapon', 'armor', 'shield'],
            'wood' => ['weapon', 'shield', 'accessory'],
            'leather' => ['armor', 'boots', 'bag'],
            'fabric' => ['armor', 'bag', 'accessory'],
            'gem' => ['accessory', 'weapon_enhancement', 'armor_enhancement'],
            'herb' => ['potion', 'consumable'],
            default => ['misc_crafting'],
        };
    }

    public function getItemInfo(): array
    {
        $info = parent::getItemInfo();
        
        return array_merge($info, [
            'material_type' => $this->getMaterialType(),
            'material_grade' => $this->getMaterialGrade(),
            'crafting_uses' => $this->getCraftingUses(),
            'sell_value' => $this->getSellValue(),
        ]);
    }

    /**
     * 素材のサンプルデータ
     */
    public static function getSampleMaterials(): array
    {
        return [
            // 鉱石系
            [
                'name' => '鉄鉱石',
                'description' => '武器や防具の素材となる鉱石',
                'category' => ItemCategory::MATERIAL,
                'value' => 5,
                'stack_limit' => 99,
                'material_type' => 'ore',
                'material_grade' => 1,
                'crafting_uses' => ['weapon', 'armor', 'shield'],
                'item_type' => 'material',
            ],
            [
                'name' => '銀鉱石',
                'description' => '上質な武器や防具を作るのに使われる銀の鉱石',
                'category' => ItemCategory::MATERIAL,
                'value' => 15,
                'stack_limit' => 99,
                'material_type' => 'ore',
                'material_grade' => 2,
                'crafting_uses' => ['weapon', 'armor', 'shield'],
                'item_type' => 'material',
            ],
            [
                'name' => 'ミスリル鉱石',
                'description' => '魔法の力を宿した希少な鉱石',
                'category' => ItemCategory::MATERIAL,
                'value' => 100,
                'stack_limit' => 50,
                'material_type' => 'ore',
                'material_grade' => 4,
                'crafting_uses' => ['weapon', 'armor', 'shield', 'accessory'],
                'item_type' => 'material',
            ],

            // 木材系
            [
                'name' => '普通の木材',
                'description' => 'ごく一般的な木材',
                'category' => ItemCategory::MATERIAL,
                'value' => 3,
                'stack_limit' => 99,
                'material_type' => 'wood',
                'material_grade' => 1,
                'crafting_uses' => ['weapon', 'shield'],
                'item_type' => 'material',
            ],
            [
                'name' => '神木の枝',
                'description' => '神聖な力を持つ樹の枝',
                'category' => ItemCategory::MATERIAL,
                'value' => 500,
                'stack_limit' => 10,
                'material_type' => 'wood',
                'material_grade' => 5,
                'crafting_uses' => ['weapon', 'accessory'],
                'item_type' => 'material',
            ],

            // 皮革系
            [
                'name' => 'ウルフの毛皮',
                'description' => 'オオカミの丈夫な毛皮',
                'category' => ItemCategory::MATERIAL,
                'value' => 20,
                'stack_limit' => 50,
                'material_type' => 'leather',
                'material_grade' => 2,
                'crafting_uses' => ['armor', 'boots', 'bag'],
                'item_type' => 'material',
            ],
            [
                'name' => 'ドラゴンレザー',
                'description' => 'ドラゴンの鱗を加工した究極の革',
                'category' => ItemCategory::MATERIAL,
                'value' => 1000,
                'stack_limit' => 5,
                'material_type' => 'leather',
                'material_grade' => 5,
                'crafting_uses' => ['armor', 'boots'],
                'item_type' => 'material',
            ],

            // 宝石系
            [
                'name' => 'ルビー',
                'description' => '炎の力を宿した赤い宝石',
                'category' => ItemCategory::MATERIAL,
                'value' => 80,
                'stack_limit' => 20,
                'material_type' => 'gem',
                'material_grade' => 3,
                'crafting_uses' => ['accessory', 'weapon_enhancement'],
                'item_type' => 'material',
            ],
            [
                'name' => 'サファイア',
                'description' => '水の力を宿した青い宝石',
                'category' => ItemCategory::MATERIAL,
                'value' => 80,
                'stack_limit' => 20,
                'material_type' => 'gem',
                'material_grade' => 3,
                'crafting_uses' => ['accessory', 'armor_enhancement'],
                'item_type' => 'material',
            ],

            // 薬草系
            [
                'name' => '薬草',
                'description' => '治癒効果のある一般的な薬草',
                'category' => ItemCategory::MATERIAL,
                'value' => 2,
                'stack_limit' => 99,
                'material_type' => 'herb',
                'material_grade' => 1,
                'crafting_uses' => ['potion', 'consumable'],
                'item_type' => 'material',
            ],
            [
                'name' => '万能薬草',
                'description' => 'あらゆる病気に効く奇跡の薬草',
                'category' => ItemCategory::MATERIAL,
                'value' => 200,
                'stack_limit' => 10,
                'material_type' => 'herb',
                'material_grade' => 4,
                'crafting_uses' => ['potion', 'consumable'],
                'item_type' => 'material',
            ],

            // その他
            [
                'name' => '魔法の粉',
                'description' => '不思議な力を持つ謎の粉',
                'category' => ItemCategory::MATERIAL,
                'value' => 25,
                'stack_limit' => 50,
                'material_type' => 'misc',
                'material_grade' => 2,
                'crafting_uses' => ['misc_crafting', 'weapon_enhancement', 'armor_enhancement'],
                'item_type' => 'material',
            ],
        ];
    }
}