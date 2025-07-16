<?php

namespace App\Contracts;

use App\Models\BattleSkill;

interface WeaponInterface extends EquippableInterface
{
    /**
     * 武器タイプの定数
     */
    const TYPE_PHYSICAL = 'physical';
    const TYPE_MAGICAL = 'magical';

    /**
     * 武器固有の機能
     */
    public function getWeaponType(): string;
    public function isPhysicalWeapon(): bool;
    public function isMagicalWeapon(): bool;
    public function getAttackPower(): int;
    public function getMagicAttackPower(): int;

    /**
     * 戦闘特技関連
     */
    public function hasBattleSkill(): bool;
    public function getBattleSkill(): ?BattleSkill;
    public function getBattleSkillId(): ?string;

    /**
     * 攻撃時の処理
     */
    public function calculateAttackDamage(array $attacker, array $target): array;
}