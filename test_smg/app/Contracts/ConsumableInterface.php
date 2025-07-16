<?php

namespace App\Contracts;

interface ConsumableInterface
{
    /**
     * 消費アイテムの使用効果
     */
    public function consume(array $target): array;

    /**
     * 使用可能条件のチェック
     */
    public function canUseOn(array $target): bool;

    /**
     * 使用時のコスト・制限
     */
    public function getUseCost(): int;
    public function hasUsageLimit(): bool;
    public function getRemainingUses(): int;

    /**
     * 効果の詳細
     */
    public function getEffectDescription(): string;
    public function getEffectType(): string;
    public function getEffectValue(): int;
}