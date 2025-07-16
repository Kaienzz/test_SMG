<?php

namespace App\Contracts;

use App\Enums\ItemCategory;

interface ItemInterface
{
    /**
     * アイテムの基本情報を取得
     */
    public function getId(): int;
    public function getName(): string;
    public function getDescription(): string;
    public function getCategory(): ItemCategory;
    public function getRarity(): int;
    public function getValue(): int;

    /**
     * アイテムの表示用情報を取得
     */
    public function getDisplayName(): string;
    public function getRarityName(): string;
    public function getRarityColor(): string;
    public function getItemInfo(): array;

    /**
     * アイテムの行動判定
     */
    public function canStack(): bool;
    public function getStackLimit(): int;
    public function isUsable(): bool;
    public function isEquippable(): bool;
    public function hasDurability(): bool;

    /**
     * アイテムの使用・効果
     */
    public function use(array $target): array;
    public function getEffects(): array;
}