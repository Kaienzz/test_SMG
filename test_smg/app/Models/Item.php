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


    public static function createItemInstance(array $itemData): self
    {
        return new self($itemData);
    }

    public static function findSampleItem(string $name): ?self
    {
        $itemData = self::findStandardItemData($name);
        
        if ($itemData) {
            $item = new self($itemData);
            $item->id = fake()->numberBetween(1, 1000);
            return $item;
        }
        
        return null;
    }
    
    /**
     * 標準アイテムデータを検索（JSON固定）
     */
    private static function findStandardItemData(string $name): ?array
    {
        try {
            $standardItemService = app(\App\Services\StandardItem\StandardItemService::class);
            $itemData = $standardItemService->findByName($name);
            
            if ($itemData) {
                return self::convertJsonToItemFormat($itemData);
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('Failed to find standard item', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    /**
     * JSONデータをItemモデル形式に変換
     */
    private static function convertJsonToItemFormat(array $jsonData): array
    {
        return [
            'name' => $jsonData['name'],
            'description' => $jsonData['description'],
            'category' => $jsonData['category'],
            'stack_limit' => $jsonData['stack_limit'],
            'max_durability' => $jsonData['max_durability'],
            'effects' => $jsonData['effects'],
            'value' => $jsonData['value'],
            'sell_price' => $jsonData['sell_price'] ?? null,
            'weapon_type' => $jsonData['weapon_type'] ?? null,
        ];
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