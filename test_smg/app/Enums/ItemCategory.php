<?php

namespace App\Enums;

enum ItemCategory: string
{
    case MATERIAL = 'material';
    case HEAD_EQUIPMENT = 'head_equipment';
    case WEAPON = 'weapon';
    case SHIELD = 'shield';
    case BODY_EQUIPMENT = 'body_equipment';
    case FOOT_EQUIPMENT = 'foot_equipment';
    case ACCESSORY = 'accessory';
    case BAG = 'bag';
    case POTION = 'potion';

    public function getDisplayName(): string
    {
        return match($this) {
            self::MATERIAL => '素材',
            self::HEAD_EQUIPMENT => '頭装備',
            self::WEAPON => '武器',
            self::SHIELD => '盾',
            self::BODY_EQUIPMENT => '胴体装備',
            self::FOOT_EQUIPMENT => '靴装備',
            self::ACCESSORY => '装飾品',
            self::BAG => '鞄',
            self::POTION => 'ポーション',
        };
    }

    public function hasStackLimit(): bool
    {
        return match($this) {
            self::MATERIAL, self::POTION => true,
            default => false,
        };
    }

    public function getDefaultStackLimit(): int
    {
        return match($this) {
            self::MATERIAL => 99,
            self::POTION => 50,
            default => 1,
        };
    }

    public function hasDurability(): bool
    {
        return match($this) {
            self::HEAD_EQUIPMENT,
            self::WEAPON,
            self::SHIELD,
            self::BODY_EQUIPMENT,
            self::FOOT_EQUIPMENT,
            self::ACCESSORY,
            self::BAG => true,
            default => false,
        };
    }

    public function getDefaultDurability(): int
    {
        return match($this) {
            self::WEAPON => 100,
            self::HEAD_EQUIPMENT,
            self::BODY_EQUIPMENT => 80,
            self::SHIELD => 90,
            self::FOOT_EQUIPMENT => 70,
            self::ACCESSORY => 60,
            self::BAG => 120,
            default => 0,
        };
    }

    public function isEquippable(): bool
    {
        return match($this) {
            self::HEAD_EQUIPMENT,
            self::WEAPON,
            self::SHIELD,
            self::BODY_EQUIPMENT,
            self::FOOT_EQUIPMENT,
            self::ACCESSORY,
            self::BAG => true,
            default => false,
        };
    }

    public function isUsable(): bool
    {
        return match($this) {
            self::POTION => true,
            default => false,
        };
    }

    public static function getAllCategories(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'name' => $case->getDisplayName(),
            'has_stack_limit' => $case->hasStackLimit(),
            'default_stack_limit' => $case->getDefaultStackLimit(),
            'has_durability' => $case->hasDurability(),
            'default_durability' => $case->getDefaultDurability(),
            'is_equippable' => $case->isEquippable(),
            'is_usable' => $case->isUsable(),
        ], self::cases());
    }
}