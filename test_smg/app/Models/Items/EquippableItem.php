<?php

namespace App\Models\Items;

use App\Contracts\EquippableInterface;

abstract class EquippableItem extends AbstractItem implements EquippableInterface
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
        'max_durability',
        'current_durability',
        'equipment_slot',
        'required_level',
        'required_stats',
    ];

    protected $casts = [
        'category' => ItemCategory::class,
        'value' => 'integer',
        'effects' => 'array',
        'stack_limit' => 'integer',
        'max_durability' => 'integer',
        'current_durability' => 'integer',
        'required_level' => 'integer',
        'required_stats' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->item_type = 'equipment';
        
        // 初期耐久度を最大耐久度に設定
        if (!isset($attributes['current_durability']) && $this->hasDurability()) {
            $this->current_durability = $this->getMaxDurability();
        }
    }

    // ItemInterface 実装
    public function use(array $target): array
    {
        return [
            'success' => false,
            'message' => '装備品は直接使用できません。装備してください。',
        ];
    }

    // EquippableInterface 実装
    public function getEquipmentSlot(): string
    {
        return $this->equipment_slot ?? $this->determineSlotFromCategory();
    }

    public function getStatModifiers(): array
    {
        if ($this->isBroken()) {
            return []; // 壊れた装備はステータス効果なし
        }
        
        return $this->getEffects();
    }

    public function getMaxDurability(): int
    {
        return $this->max_durability ?? $this->category->getDefaultDurability();
    }

    public function getCurrentDurability(): int
    {
        return $this->current_durability ?? $this->getMaxDurability();
    }

    public function takeDamage(int $damage): void
    {
        $this->current_durability = max(0, $this->getCurrentDurability() - $damage);
        $this->save();
    }

    public function repair(int $amount): void
    {
        $this->current_durability = min(
            $this->getMaxDurability(),
            $this->getCurrentDurability() + $amount
        );
        $this->save();
    }

    public function isBroken(): bool
    {
        return $this->hasDurability() && $this->getCurrentDurability() <= 0;
    }

    public function canEquipBy(array $character): bool
    {
        // レベル要件チェック
        $requiredLevel = $this->required_level ?? 1;
        if (($character['level'] ?? 1) < $requiredLevel) {
            return false;
        }

        // ステータス要件チェック
        $requiredStats = $this->required_stats ?? [];
        foreach ($requiredStats as $stat => $requiredValue) {
            if (($character[$stat] ?? 0) < $requiredValue) {
                return false;
            }
        }

        return true;
    }

    // AbstractItemのhasDurability()をオーバーライド
    public function hasDurability(): bool
    {
        return $this->category->hasDurability();
    }

    private function determineSlotFromCategory(): string
    {
        return match($this->category->value) {
            'weapon' => 'weapon',
            'head_equipment' => 'helmet',
            'body_equipment' => 'body_armor',
            'foot_equipment' => 'boots',
            'shield' => 'shield',
            'accessory' => 'accessory',
            'bag' => 'bag',
            default => 'unknown',
        };
    }

    public function getDurabilityPercentage(): float
    {
        if (!$this->hasDurability()) {
            return 100.0;
        }
        
        $max = $this->getMaxDurability();
        if ($max <= 0) {
            return 0.0;
        }
        
        return ($this->getCurrentDurability() / $max) * 100;
    }

    public function getDurabilityStatus(): string
    {
        if (!$this->hasDurability()) {
            return 'perfect';
        }
        
        $percentage = $this->getDurabilityPercentage();
        
        return match(true) {
            $percentage <= 0 => 'broken',
            $percentage <= 25 => 'critical',
            $percentage <= 50 => 'damaged',
            $percentage <= 75 => 'worn',
            default => 'good',
        };
    }

    public function getItemInfo(): array
    {
        $info = parent::getItemInfo();
        
        return array_merge($info, [
            'equipment_slot' => $this->getEquipmentSlot(),
            'stat_modifiers' => $this->getStatModifiers(),
            'max_durability' => $this->getMaxDurability(),
            'current_durability' => $this->getCurrentDurability(),
            'durability_percentage' => $this->getDurabilityPercentage(),
            'durability_status' => $this->getDurabilityStatus(),
            'is_broken' => $this->isBroken(),
            'required_level' => $this->required_level ?? 1,
            'required_stats' => $this->required_stats ?? [],
        ]);
    }
}