<?php

namespace App\Models\Items;

use App\Contracts\ConsumableInterface;
use App\Enums\ItemCategory;

class ConsumableItem extends AbstractItem implements ConsumableInterface
{
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
        'item_type',
        'effect_type',
        'effect_value',
        'usage_limit',
        'remaining_uses',
    ];

    protected $casts = [
        'category' => ItemCategory::class,
        'rarity' => 'integer',
        'value' => 'integer',
        'effects' => 'array',
        'stack_limit' => 'integer',
        'effect_value' => 'integer',
        'usage_limit' => 'integer',
        'remaining_uses' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->item_type = 'consumable';
    }

    // ItemInterface 実装
    public function use(array $target): array
    {
        return $this->consume($target);
    }

    // ConsumableInterface 実装
    public function consume(array $target): array
    {
        if (!$this->canUseOn($target)) {
            return [
                'success' => false,
                'message' => 'このアイテムは使用できません。',
            ];
        }

        $result = $this->applyEffect($target);
        
        // 使用回数制限がある場合は減らす
        if ($this->hasUsageLimit()) {
            $this->remaining_uses = max(0, $this->remaining_uses - 1);
            $this->save();
        }

        return $result;
    }

    public function canUseOn(array $target): bool
    {
        // 使用回数制限チェック
        if ($this->hasUsageLimit() && $this->getRemainingUses() <= 0) {
            return false;
        }

        // HP回復アイテムの場合、HPが満タンでないかチェック
        if ($this->getEffectType() === 'heal_hp') {
            return ($target['hp'] ?? 0) < ($target['max_hp'] ?? 1);
        }

        // MP回復アイテムの場合、MPが満タンでないかチェック
        if ($this->getEffectType() === 'heal_mp') {
            return ($target['mp'] ?? 0) < ($target['max_mp'] ?? 1);
        }

        return true;
    }

    public function getUseCost(): int
    {
        return 1; // 消費アイテムは通常1個消費
    }

    public function hasUsageLimit(): bool
    {
        return !is_null($this->usage_limit);
    }

    public function getRemainingUses(): int
    {
        return $this->remaining_uses ?? $this->usage_limit ?? 1;
    }

    public function getEffectDescription(): string
    {
        $effectType = $this->getEffectType();
        $effectValue = $this->getEffectValue();

        return match($effectType) {
            'heal_hp' => "HPを{$effectValue}回復",
            'heal_mp' => "MPを{$effectValue}回復",
            'heal_sp' => "SPを{$effectValue}回復",
            'restore_all' => "HP・MP・SPを全回復",
            'buff_attack' => "攻撃力を{$effectValue}上昇（一時的）",
            'buff_defense' => "防御力を{$effectValue}上昇（一時的）",
            'cure_status' => "状態異常を回復",
            default => "特殊効果",
        };
    }

    public function getEffectType(): string
    {
        return $this->effect_type ?? $this->determineEffectTypeFromEffects();
    }

    public function getEffectValue(): int
    {
        return $this->effect_value ?? $this->determineEffectValueFromEffects();
    }

    private function determineEffectTypeFromEffects(): string
    {
        $effects = $this->getEffects();
        
        if (isset($effects['heal_hp'])) return 'heal_hp';
        if (isset($effects['heal_mp'])) return 'heal_mp';
        if (isset($effects['heal_sp'])) return 'heal_sp';
        if (isset($effects['restore_all'])) return 'restore_all';
        if (isset($effects['buff_attack'])) return 'buff_attack';
        if (isset($effects['buff_defense'])) return 'buff_defense';
        if (isset($effects['cure_status'])) return 'cure_status';
        
        return 'unknown';
    }

    private function determineEffectValueFromEffects(): int
    {
        $effects = $this->getEffects();
        $effectType = $this->getEffectType();
        
        return $effects[$effectType] ?? 0;
    }

    private function applyEffect(array $target): array
    {
        $effectType = $this->getEffectType();
        $effectValue = $this->getEffectValue();
        $targetName = $target['name'] ?? 'ターゲット';

        switch ($effectType) {
            case 'heal_hp':
                $beforeHp = $target['hp'] ?? 0;
                $maxHp = $target['max_hp'] ?? 1;
                $target['hp'] = min($maxHp, $beforeHp + $effectValue);
                $actualHealing = $target['hp'] - $beforeHp;
                
                return [
                    'success' => true,
                    'message' => "{$targetName}のHPが{$actualHealing}回復した！",
                    'target' => $target,
                    'effect' => ['hp_healed' => $actualHealing],
                ];

            case 'heal_mp':
                $beforeMp = $target['mp'] ?? 0;
                $maxMp = $target['max_mp'] ?? 1;
                $target['mp'] = min($maxMp, $beforeMp + $effectValue);
                $actualHealing = $target['mp'] - $beforeMp;
                
                return [
                    'success' => true,
                    'message' => "{$targetName}のMPが{$actualHealing}回復した！",
                    'target' => $target,
                    'effect' => ['mp_healed' => $actualHealing],
                ];

            case 'heal_sp':
                $beforeSp = $target['sp'] ?? 0;
                $maxSp = $target['max_sp'] ?? 1;
                $target['sp'] = min($maxSp, $beforeSp + $effectValue);
                $actualHealing = $target['sp'] - $beforeSp;
                
                return [
                    'success' => true,
                    'message' => "{$targetName}のSPが{$actualHealing}回復した！",
                    'target' => $target,
                    'effect' => ['sp_healed' => $actualHealing],
                ];

            case 'restore_all':
                $target['hp'] = $target['max_hp'] ?? 1;
                $target['mp'] = $target['max_mp'] ?? 1;
                $target['sp'] = $target['max_sp'] ?? 1;
                
                return [
                    'success' => true,
                    'message' => "{$targetName}のHP・MP・SPが全回復した！",
                    'target' => $target,
                    'effect' => ['full_restore' => true],
                ];

            default:
                return [
                    'success' => false,
                    'message' => '不明な効果です。',
                ];
        }
    }

    public function getItemInfo(): array
    {
        $info = parent::getItemInfo();
        
        return array_merge($info, [
            'effect_type' => $this->getEffectType(),
            'effect_value' => $this->getEffectValue(),
            'effect_description' => $this->getEffectDescription(),
            'has_usage_limit' => $this->hasUsageLimit(),
            'remaining_uses' => $this->getRemainingUses(),
            'use_cost' => $this->getUseCost(),
        ]);
    }

    /**
     * 消費アイテムのサンプルデータ
     */
    public static function getSampleConsumables(): array
    {
        return [
            [
                'name' => '薬草',
                'description' => 'HPを20回復する薬草',
                'category' => ItemCategory::POTION,
                'rarity' => 1,
                'value' => 10,
                'effects' => ['heal_hp' => 20],
                'effect_type' => 'heal_hp',
                'effect_value' => 20,
                'stack_limit' => 50,
                'item_type' => 'consumable',
            ],
            [
                'name' => 'マナポーション',
                'description' => 'MPを30回復するポーション',
                'category' => ItemCategory::POTION,
                'rarity' => 1,
                'value' => 15,
                'effects' => ['heal_mp' => 30],
                'effect_type' => 'heal_mp',
                'effect_value' => 30,
                'stack_limit' => 50,
                'item_type' => 'consumable',
            ],
            [
                'name' => 'ハイポーション',
                'description' => 'HPを60回復する高級ポーション',
                'category' => ItemCategory::POTION,
                'rarity' => 2,
                'value' => 50,
                'effects' => ['heal_hp' => 60],
                'effect_type' => 'heal_hp',
                'effect_value' => 60,
                'stack_limit' => 30,
                'item_type' => 'consumable',
            ],
            [
                'name' => 'エリクサー',
                'description' => 'HP・MP・SPを全回復する万能薬',
                'category' => ItemCategory::POTION,
                'rarity' => 4,
                'value' => 500,
                'effects' => ['restore_all' => true],
                'effect_type' => 'restore_all',
                'effect_value' => 1,
                'stack_limit' => 5,
                'item_type' => 'consumable',
            ],
        ];
    }
}