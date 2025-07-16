<?php

namespace App\Contracts;

interface EquippableInterface
{
    /**
     * 装備可能な部位を取得
     */
    public function getEquipmentSlot(): string;

    /**
     * 装備時のステータス効果を取得
     */
    public function getStatModifiers(): array;

    /**
     * 耐久度関連
     */
    public function getMaxDurability(): int;
    public function getCurrentDurability(): int;
    public function takeDamage(int $damage): void;
    public function repair(int $amount): void;
    public function isBroken(): bool;

    /**
     * 装備可能条件のチェック
     */
    public function canEquipBy(array $character): bool;
}