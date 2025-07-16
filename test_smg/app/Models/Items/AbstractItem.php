<?php

namespace App\Models\Items;

use App\Contracts\ItemInterface;
use App\Enums\ItemCategory;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractItem extends Model implements ItemInterface
{
    protected $table = 'items';

    protected $fillable = [
        'name',
        'description',
        'category',
        'rarity',
        'value',
        'effects',
        'stack_limit',
        'battle_skill_id',
        'weapon_type',
        'item_type', // 新しく追加：具象クラスの識別用
    ];

    protected $casts = [
        'category' => ItemCategory::class,
        'rarity' => 'integer',
        'value' => 'integer',
        'effects' => 'array',
        'stack_limit' => 'integer',
    ];

    // ItemInterface の基本実装
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): ItemCategory
    {
        return $this->category;
    }

    public function getRarity(): int
    {
        return $this->rarity;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getDisplayName(): string
    {
        return $this->name;
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

    public function canStack(): bool
    {
        return $this->category->hasStackLimit();
    }

    public function getStackLimit(): int
    {
        return $this->stack_limit ?? $this->category->getDefaultStackLimit();
    }

    public function isUsable(): bool
    {
        return $this->category->isUsable();
    }

    public function isEquippable(): bool
    {
        return $this->category->isEquippable();
    }

    public function hasDurability(): bool
    {
        // デフォルトでは耐久度なし、装備品クラスでオーバーライド
        return false;
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

    public function getItemInfo(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'category' => $this->getCategory()->value,
            'category_name' => $this->getCategory()->getDisplayName(),
            'rarity' => $this->getRarity(),
            'rarity_name' => $this->getRarityName(),
            'rarity_color' => $this->getRarityColor(),
            'value' => $this->getValue(),
            'effects' => $this->getEffects(),
            'can_stack' => $this->canStack(),
            'stack_limit' => $this->getStackLimit(),
            'is_usable' => $this->isUsable(),
            'is_equippable' => $this->isEquippable(),
            'has_durability' => $this->hasDurability(),
            'item_type' => $this->item_type,
        ];
    }

    // 抽象メソッド：具象クラスで実装
    abstract public function use(array $target): array;
}