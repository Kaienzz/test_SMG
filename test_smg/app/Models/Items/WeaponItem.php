<?php

namespace App\Models\Items;

use App\Contracts\WeaponInterface;
use App\Models\BattleSkill;
use App\Enums\ItemCategory;

class WeaponItem extends EquippableItem implements WeaponInterface
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->item_type = 'weapon';
        $this->equipment_slot = 'weapon';
        
        // デフォルトの武器タイプを設定
        if (!isset($attributes['weapon_type'])) {
            $this->weapon_type = self::TYPE_PHYSICAL;
        }
    }

    // WeaponInterface 実装
    public function getWeaponType(): string
    {
        return $this->weapon_type ?? self::TYPE_PHYSICAL;
    }

    public function isPhysicalWeapon(): bool
    {
        return $this->getWeaponType() === self::TYPE_PHYSICAL;
    }

    public function isMagicalWeapon(): bool
    {
        return $this->getWeaponType() === self::TYPE_MAGICAL;
    }

    public function getAttackPower(): int
    {
        return $this->getEffectValue('attack');
    }

    public function getMagicAttackPower(): int
    {
        return $this->getEffectValue('magic_attack');
    }

    public function hasBattleSkill(): bool
    {
        return !empty($this->battle_skill_id);
    }

    public function getBattleSkill(): ?BattleSkill
    {
        if (!$this->hasBattleSkill()) {
            return null;
        }
        
        return BattleSkill::getSkillById($this->battle_skill_id);
    }

    public function getBattleSkillId(): ?string
    {
        return $this->battle_skill_id;
    }

    public function calculateAttackDamage(array $attacker, array $target): array
    {
        if ($this->isBroken()) {
            return [
                'hit' => false,
                'damage' => 0,
                'critical' => false,
                'message' => '武器が壊れているため攻撃できない！',
                'attack_type' => $this->getWeaponType(),
            ];
        }

        // 武器タイプに応じた攻撃力を取得
        $weaponPower = $this->isMagicalWeapon() 
            ? $this->getMagicAttackPower() 
            : $this->getAttackPower();
            
        $attackerStat = $this->isMagicalWeapon() 
            ? ($attacker['magic_attack'] ?? 0) 
            : ($attacker['attack'] ?? 0);
            
        $totalAttack = $weaponPower + $attackerStat;
        $defense = $target['defense'] ?? 0;
        $attackerAccuracy = $attacker['accuracy'] ?? 80;
        $targetEvasion = $target['evasion'] ?? 10;
        
        // 命中判定
        $hitChance = max(10, $attackerAccuracy - $targetEvasion);
        $hitRoll = mt_rand(1, 100);
        
        if ($hitRoll > $hitChance) {
            $attackName = $this->isMagicalWeapon() ? '魔法攻撃' : '攻撃';
            return [
                'hit' => false,
                'damage' => 0,
                'critical' => false,
                'message' => $attackName . 'は外れた！',
                'attack_type' => $this->getWeaponType(),
            ];
        }
        
        // ダメージ計算
        $baseDamage = max(1, $totalAttack - $defense);
        $randomMultiplier = mt_rand(80, 120) / 100;
        $damage = (int) round($baseDamage * $randomMultiplier);
        
        // クリティカル判定
        $critical = mt_rand(1, 100) <= 5;
        if ($critical) {
            $damage = (int) round($damage * 1.5);
        }
        
        // 武器の耐久度を減らす
        $this->takeDamage(1);
        
        return [
            'hit' => true,
            'damage' => $damage,
            'critical' => $critical,
            'message' => $critical ? 'クリティカルヒット！' : '',
            'attack_type' => $this->getWeaponType(),
            'weapon_damage_taken' => 1,
        ];
    }

    public function getItemInfo(): array
    {
        $info = parent::getItemInfo();
        
        return array_merge($info, [
            'weapon_type' => $this->getWeaponType(),
            'is_physical_weapon' => $this->isPhysicalWeapon(),
            'is_magical_weapon' => $this->isMagicalWeapon(),
            'attack_power' => $this->getAttackPower(),
            'magic_attack_power' => $this->getMagicAttackPower(),
            'has_battle_skill' => $this->hasBattleSkill(),
            'battle_skill_id' => $this->getBattleSkillId(),
            'battle_skill' => $this->getBattleSkill()?->getSkillInfo(),
        ]);
    }

    /**
     * 武器のサンプルデータ
     */
    public static function getSampleWeapons(): array
    {
        return [
            [
                'name' => '鉄の剣',
                'description' => '攻撃力+5の基本的な剣',
                'category' => ItemCategory::WEAPON,
                'rarity' => 1,
                'value' => 100,
                'effects' => ['attack' => 5],
                'max_durability' => 100,
                'weapon_type' => self::TYPE_PHYSICAL,
                'item_type' => 'weapon',
            ],
            [
                'name' => 'ファイヤーロッド',
                'description' => '魔法攻撃力+6、ファイヤー魔法を使える杖',
                'category' => ItemCategory::WEAPON,
                'rarity' => 2,
                'value' => 150,
                'effects' => ['magic_attack' => 6],
                'max_durability' => 80,
                'weapon_type' => self::TYPE_MAGICAL,
                'battle_skill_id' => 'fire_magic',
                'item_type' => 'weapon',
            ],
            [
                'name' => 'アイスワンド',
                'description' => '魔法攻撃力+5、アイス魔法を使える杖',
                'category' => ItemCategory::WEAPON,
                'rarity' => 2,
                'value' => 140,
                'effects' => ['magic_attack' => 5],
                'max_durability' => 80,
                'weapon_type' => self::TYPE_MAGICAL,
                'battle_skill_id' => 'ice_magic',
                'item_type' => 'weapon',
            ],
            [
                'name' => 'ミスリルソード',
                'description' => '攻撃力+12、命中力+5の魔法の剣',
                'category' => ItemCategory::WEAPON,
                'rarity' => 3,
                'value' => 300,
                'effects' => ['attack' => 12, 'accuracy' => 5],
                'max_durability' => 150,
                'weapon_type' => self::TYPE_PHYSICAL,
                'battle_skill_id' => 'power_strike',
                'required_level' => 5,
                'item_type' => 'weapon',
            ],
            [
                'name' => 'サンダースタッフ',
                'description' => '魔法攻撃力+10、サンダー魔法を使える強力な杖',
                'category' => ItemCategory::WEAPON,
                'rarity' => 3,
                'value' => 350,
                'effects' => ['magic_attack' => 10, 'mp' => 20],
                'max_durability' => 120,
                'weapon_type' => self::TYPE_MAGICAL,
                'battle_skill_id' => 'thunder_magic',
                'required_level' => 8,
                'required_stats' => ['magic_attack' => 15],
                'item_type' => 'weapon',
            ],
        ];
    }
}