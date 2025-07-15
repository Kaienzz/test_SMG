<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveEffect extends Model
{
    protected $fillable = [
        'character_id',
        'effect_type',
        'effect_name',
        'source_type',
        'source_id',
        'effects',
        'duration',
        'remaining_duration',
        'is_active',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'source_id' => 'integer',
        'effects' => 'array',
        'duration' => 'integer',
        'remaining_duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function getMovementEffects(): array
    {
        if (!$this->is_active || $this->remaining_duration <= 0) {
            return [
                'dice_bonus' => 0,
                'extra_dice' => 0,
                'movement_multiplier' => 1.0,
                'special_effects' => [],
            ];
        }

        $effects = $this->effects ?? [];
        
        return [
            'dice_bonus' => $effects['dice_bonus'] ?? 0,
            'extra_dice' => $effects['extra_dice'] ?? 0,
            'movement_multiplier' => $effects['movement_multiplier'] ?? 1.0,
            'special_effects' => array_filter($effects, function($key) {
                return !in_array($key, ['dice_bonus', 'extra_dice', 'movement_multiplier']);
            }, ARRAY_FILTER_USE_KEY),
        ];
    }

    public function decreaseDuration(int $amount = 1): bool
    {
        $this->remaining_duration = max(0, $this->remaining_duration - $amount);
        
        if ($this->remaining_duration <= 0) {
            $this->is_active = false;
        }
        
        $this->save();
        
        return $this->is_active;
    }

    public static function createTemporaryEffect(
        int $characterId, 
        string $effectName, 
        array $effects, 
        int $duration,
        string $sourceType = 'item',
        int $sourceId = null
    ): self {
        return self::create([
            'character_id' => $characterId,
            'effect_type' => 'temporary',
            'effect_name' => $effectName,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'effects' => $effects,
            'duration' => $duration,
            'remaining_duration' => $duration,
            'is_active' => true,
        ]);
    }

    public static function getSampleEffects(): array
    {
        return [
            [
                'effect_name' => '疾風のポーション',
                'effects' => ['extra_dice' => 1, 'dice_bonus' => 2],
                'duration' => 5,
                'description' => '5ターンの間、追加サイコロ+1、サイコロボーナス+2',
            ],
            [
                'effect_name' => '巨大化薬',
                'effects' => ['movement_multiplier' => 0.5],
                'duration' => 3,
                'description' => '3ターンの間、移動距離1.5倍',
            ],
            [
                'effect_name' => '俊敏性向上',
                'effects' => ['dice_bonus' => 3],
                'duration' => 10,
                'description' => '10ターンの間、サイコロボーナス+3',
            ],
        ];
    }
}