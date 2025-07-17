<?php

namespace App\Models;

use App\Enums\ItemCategory;
use App\Factories\ItemFactory;
use App\Contracts\ItemInterface;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /**
     * 武器タイプの定数
     */
    const WEAPON_TYPE_PHYSICAL = 'physical';
    const WEAPON_TYPE_MAGICAL = 'magical';

    protected $fillable = [
        'name',
        'description',
        'category',
        'stack_limit',
        'max_durability',
        'effects',
        'rarity',
        'value',
        'sell_price',
        'battle_skill_id',
        'weapon_type',
    ];

    protected $casts = [
        'category' => ItemCategory::class,
        'stack_limit' => 'integer',
        'max_durability' => 'integer',
        'effects' => 'array',
        'rarity' => 'integer',
        'value' => 'integer',
        'sell_price' => 'integer',
    ];

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getCategoryName(): string
    {
        return $this->category->getDisplayName();
    }

    public function hasStackLimit(): bool
    {
        return $this->category->hasStackLimit();
    }

    public function getStackLimit(): int
    {
        return $this->stack_limit ?? $this->category->getDefaultStackLimit();
    }

    public function hasDurability(): bool
    {
        return $this->category->hasDurability();
    }

    public function getMaxDurability(): int
    {
        return $this->max_durability ?? $this->category->getDefaultDurability();
    }

    public function isEquippable(): bool
    {
        return $this->category->isEquippable();
    }

    public function isUsable(): bool
    {
        return $this->category->isUsable();
    }

    public function getEffects(): array
    {
        return $this->effects ?? [];
    }

    public function hasEffect(string $effectType): bool
    {
        $effects = $this->getEffects();
        return isset($effects[$effectType]);
    }

    public function getEffectValue(string $effectType): int
    {
        $effects = $this->getEffects();
        return $effects[$effectType] ?? 0;
    }

    public function isWeapon(): bool
    {
        return $this->category === ItemCategory::WEAPON;
    }

    public function isMagicalWeapon(): bool
    {
        return $this->isWeapon() && $this->weapon_type === self::WEAPON_TYPE_MAGICAL;
    }

    public function isPhysicalWeapon(): bool
    {
        return $this->isWeapon() && $this->weapon_type === self::WEAPON_TYPE_PHYSICAL;
    }

    public function getBattleSkill(): ?BattleSkill
    {
        if (!$this->battle_skill_id) {
            return null;
        }
        return BattleSkill::getSkillById($this->battle_skill_id);
    }

    public function hasBattleSkill(): bool
    {
        return !empty($this->battle_skill_id);
    }

    public function getRarityName(): string
    {
        return match($this->rarity) {
            1 => 'コモン',
            2 => 'アンコモン',
            3 => 'レア',
            4 => 'エピック',
            5 => 'レジェンダリー',
            default => 'コモン',
        };
    }

    public function getRarityColor(): string
    {
        return match($this->rarity) {
            1 => '#9ca3af', // グレー
            2 => '#10b981', // グリーン
            3 => '#3b82f6', // ブルー
            4 => '#8b5cf6', // パープル
            5 => '#f59e0b', // オレンジ
            default => '#9ca3af',
        };
    }

    public function getSellPrice(): int
    {
        return $this->sell_price ?? (int)($this->value * 0.5);
    }

    public function getItemInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category->value,
            'category_name' => $this->getCategoryName(),
            'stack_limit' => $this->getStackLimit(),
            'max_durability' => $this->getMaxDurability(),
            'effects' => $this->getEffects(),
            'rarity' => $this->rarity,
            'rarity_name' => $this->getRarityName(),
            'rarity_color' => $this->getRarityColor(),
            'value' => $this->value,
            'sell_price' => $this->getSellPrice(),
            'is_equippable' => $this->isEquippable(),
            'is_usable' => $this->isUsable(),
            'has_durability' => $this->hasDurability(),
            'has_stack_limit' => $this->hasStackLimit(),
            'battle_skill_id' => $this->battle_skill_id,
            'weapon_type' => $this->weapon_type,
            'is_weapon' => $this->isWeapon(),
            'is_magical_weapon' => $this->isMagicalWeapon(),
            'is_physical_weapon' => $this->isPhysicalWeapon(),
            'has_battle_skill' => $this->hasBattleSkill(),
            'battle_skill' => $this->getBattleSkill()?->getSkillInfo(),
        ];
    }

    public static function createSampleItems(): array
    {
        return [
            [
                'name' => '薬草',
                'description' => 'HPを20回復する薬草',
                'category' => ItemCategory::POTION,
                'stack_limit' => 50,
                'effects' => ['heal_hp' => 20],
                'rarity' => 1,
                'value' => 10,
            ],
            [
                'name' => 'マナポーション',
                'description' => 'MPを30回復するポーション',
                'category' => ItemCategory::POTION,
                'stack_limit' => 50,
                'effects' => ['heal_mp' => 30],
                'rarity' => 1,
                'value' => 15,
            ],
            [
                'name' => '鉄の剣',
                'description' => '攻撃力+5の基本的な剣',
                'category' => ItemCategory::WEAPON,
                'max_durability' => 100,
                'effects' => ['attack' => 5],
                'rarity' => 1,
                'value' => 100,
                'weapon_type' => self::WEAPON_TYPE_PHYSICAL,
            ],
            [
                'name' => 'ファイヤーロッド',
                'description' => '魔法攻撃力+6、ファイヤー魔法を使える杖',
                'category' => ItemCategory::WEAPON,
                'max_durability' => 80,
                'effects' => ['magic_attack' => 6],
                'rarity' => 2,
                'value' => 150,
                'weapon_type' => self::WEAPON_TYPE_MAGICAL,
                'battle_skill_id' => 'fire_magic',
            ],
            [
                'name' => 'アイスワンド',
                'description' => '魔法攻撃力+5、アイス魔法を使える杖',
                'category' => ItemCategory::WEAPON,
                'max_durability' => 80,
                'effects' => ['magic_attack' => 5],
                'rarity' => 2,
                'value' => 140,
                'weapon_type' => self::WEAPON_TYPE_MAGICAL,
                'battle_skill_id' => 'ice_magic',
            ],
            [
                'name' => '革の鎧',
                'description' => '防御力+3の軽装鎧',
                'category' => ItemCategory::BODY_EQUIPMENT,
                'max_durability' => 80,
                'effects' => ['defense' => 3],
                'rarity' => 1,
                'value' => 80,
            ],
            [
                'name' => '木の盾',
                'description' => '防御力+2の基本的な盾',
                'category' => ItemCategory::SHIELD,
                'max_durability' => 90,
                'effects' => ['defense' => 2],
                'rarity' => 1,
                'value' => 60,
            ],
            [
                'name' => '鉄鉱石',
                'description' => '武器や防具の素材となる鉱石',
                'category' => ItemCategory::MATERIAL,
                'stack_limit' => 99,
                'rarity' => 1,
                'value' => 5,
            ],
            [
                'name' => '小さな袋',
                'description' => 'インベントリを2枠拡張する袋',
                'category' => ItemCategory::BAG,
                'max_durability' => 120,
                'effects' => ['inventory_slots' => 2],
                'rarity' => 2,
                'value' => 200,
            ],
        ];
    }

    public static function getItemsByCategory(ItemCategory $category): array
    {
        $sampleItems = self::createSampleItems();
        return array_filter($sampleItems, fn($item) => $item['category'] === $category);
    }

    public static function createItemInstance(array $itemData): self
    {
        return new self($itemData);
    }

    public static function findSampleItem(string $name): ?self
    {
        $sampleItems = self::createSampleItems();
        $itemData = collect($sampleItems)->firstWhere('name', $name);
        
        if ($itemData) {
            $item = new self($itemData);
            $item->id = fake()->numberBetween(1, 1000);
            return $item;
        }
        
        return null;
    }

    /**
     * 新しいアイテムシステムのインスタンスに変換
     */
    public function toNewItemSystem(): ItemInterface
    {
        return ItemFactory::fromExistingItem($this);
    }

    /**
     * 新しいアイテムシステムから既存のItemモデルを作成
     */
    public static function fromNewItemSystem(ItemInterface $newItem): self
    {
        $data = $newItem->getItemInfo();
        
        // 新しいシステムのデータを既存のシステムに適合させる
        $legacyData = [
            'name' => $data['name'],
            'description' => $data['description'],
            'category' => $data['category'],
            'rarity' => $data['rarity'],
            'value' => $data['value'],
            'effects' => $data['effects'],
            'stack_limit' => $data['stack_limit'] ?? null,
            'max_durability' => $data['max_durability'] ?? null,
        ];

        // 武器固有のデータ
        if (isset($data['weapon_type'])) {
            $legacyData['weapon_type'] = $data['weapon_type'];
        }
        if (isset($data['battle_skill_id'])) {
            $legacyData['battle_skill_id'] = $data['battle_skill_id'];
        }

        return new self($legacyData);
    }

    /**
     * 新しいアイテムシステムとの互換性チェック
     */
    public function isCompatibleWithNewSystem(): bool
    {
        try {
            $this->toNewItemSystem();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 新しいアイテムシステムを使用してアイテムを使用
     */
    public function useWithNewSystem(array $target): array
    {
        $newItem = $this->toNewItemSystem();
        return $newItem->use($target);
    }

    /**
     * アイテムの詳細情報を新しいシステム形式で取得
     */
    public function getDetailedInfo(): array
    {
        $newItem = $this->toNewItemSystem();
        return $newItem->getItemInfo();
    }

    /**
     * バックワード互換性のためのヘルパーメソッド
     */
    public function getNewSystemItemType(): string
    {
        $newItem = $this->toNewItemSystem();
        return $newItem->item_type ?? 'unknown';
    }
}