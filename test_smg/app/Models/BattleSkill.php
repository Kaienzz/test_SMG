<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BattleSkill extends Model
{
    protected $fillable = [
        'skill_id',
        'name',
        'description',
        'mp_cost',
        'skill_type',
        'base_power',
        'accuracy',
        'target_type',
        'element',
        'effects',
    ];

    protected $casts = [
        'skill_id' => 'string',
        'mp_cost' => 'integer',
        'base_power' => 'integer',
        'accuracy' => 'integer',
        'effects' => 'array',
    ];

    /**
     * スキルタイプの定数
     */
    const TYPE_PHYSICAL = 'physical';
    const TYPE_MAGICAL = 'magical';
    const TYPE_SUPPORT = 'support';

    /**
     * ターゲットタイプの定数
     */
    const TARGET_ENEMY = 'enemy';
    const TARGET_SELF = 'self';
    const TARGET_ALL_ENEMIES = 'all_enemies';

    /**
     * 属性の定数
     */
    const ELEMENT_FIRE = 'fire';
    const ELEMENT_ICE = 'ice';
    const ELEMENT_THUNDER = 'thunder';
    const ELEMENT_WIND = 'wind';
    const ELEMENT_EARTH = 'earth';
    const ELEMENT_LIGHT = 'light';
    const ELEMENT_DARK = 'dark';
    const ELEMENT_NONE = 'none';

    /**
     * スキル情報を取得
     */
    public function getSkillInfo(): array
    {
        return [
            'skill_id' => $this->skill_id,
            'name' => $this->name,
            'description' => $this->description,
            'mp_cost' => $this->mp_cost,
            'skill_type' => $this->skill_type,
            'base_power' => $this->base_power,
            'accuracy' => $this->accuracy,
            'target_type' => $this->target_type,
            'element' => $this->element,
            'effects' => $this->effects,
        ];
    }

    /**
     * ダメージを計算
     */
    public function calculateDamage(array $user, array $target): array
    {
        if ($this->skill_type === self::TYPE_SUPPORT) {
            return [
                'damage' => 0,
                'hit' => true,
                'critical' => false,
                'message' => $this->name . 'を使用した！'
            ];
        }

        // 攻撃力の計算
        $attackPower = $this->skill_type === self::TYPE_MAGICAL 
            ? ($user['magic_attack'] ?? 0) 
            : ($user['attack'] ?? 0);

        $baseDamage = $this->base_power + $attackPower;
        $defense = $target['defense'] ?? 0;

        // 命中判定
        $userAccuracy = $user['accuracy'] ?? 80;
        $targetEvasion = $target['evasion'] ?? 10;
        $skillAccuracy = $this->accuracy;
        
        $hitChance = max(10, $userAccuracy + $skillAccuracy - $targetEvasion);
        $hitRoll = mt_rand(1, 100);

        if ($hitRoll > $hitChance) {
            return [
                'hit' => false,
                'damage' => 0,
                'critical' => false,
                'message' => $this->name . 'は外れた！'
            ];
        }

        // ダメージ計算
        $damage = max(1, $baseDamage - $defense);
        $randomMultiplier = mt_rand(80, 120) / 100;
        $damage = (int) round($damage * $randomMultiplier);

        // クリティカル判定
        $critical = mt_rand(1, 100) <= 10; // 10%の確率
        if ($critical) {
            $damage = (int) round($damage * 1.5);
        }

        return [
            'hit' => true,
            'damage' => $damage,
            'critical' => $critical,
            'message' => $critical ? $this->name . 'でクリティカルヒット！' : $this->name . 'が命中！'
        ];
    }

    /**
     * スキルIDでスキルを取得
     */
    public static function getSkillById(string $skillId): ?self
    {
        return self::where('skill_id', $skillId)->first();
    }

    /**
     * デフォルトの戦闘スキルデータを取得
     */
    public static function getDefaultSkills(): array
    {
        return [
            [
                'skill_id' => 'fire_magic',
                'name' => 'ファイヤー',
                'description' => '敵に炎属性の魔法ダメージを与える',
                'mp_cost' => 5,
                'skill_type' => self::TYPE_MAGICAL,
                'base_power' => 15,
                'accuracy' => 85,
                'target_type' => self::TARGET_ENEMY,
                'element' => self::ELEMENT_FIRE,
                'effects' => [],
            ],
            [
                'skill_id' => 'ice_magic',
                'name' => 'アイス',
                'description' => '敵に氷属性の魔法ダメージを与える',
                'mp_cost' => 5,
                'skill_type' => self::TYPE_MAGICAL,
                'base_power' => 12,
                'accuracy' => 90,
                'target_type' => self::TARGET_ENEMY,
                'element' => self::ELEMENT_ICE,
                'effects' => [],
            ],
            [
                'skill_id' => 'thunder_magic',
                'name' => 'サンダー',
                'description' => '敵に雷属性の魔法ダメージを与える',
                'mp_cost' => 6,
                'skill_type' => self::TYPE_MAGICAL,
                'base_power' => 18,
                'accuracy' => 80,
                'target_type' => self::TARGET_ENEMY,
                'element' => self::ELEMENT_THUNDER,
                'effects' => [],
            ],
            [
                'skill_id' => 'heal',
                'name' => 'ヒール',
                'description' => '自分のHPを回復する',
                'mp_cost' => 4,
                'skill_type' => self::TYPE_SUPPORT,
                'base_power' => 25,
                'accuracy' => 100,
                'target_type' => self::TARGET_SELF,
                'element' => self::ELEMENT_LIGHT,
                'effects' => ['heal_hp' => true],
            ],
            [
                'skill_id' => 'power_strike',
                'name' => 'パワーストライク',
                'description' => '強力な物理攻撃を繰り出す',
                'mp_cost' => 3,
                'skill_type' => self::TYPE_PHYSICAL,
                'base_power' => 20,
                'accuracy' => 75,
                'target_type' => self::TARGET_ENEMY,
                'element' => self::ELEMENT_NONE,
                'effects' => [],
            ],
        ];
    }
}