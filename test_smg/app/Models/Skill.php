<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    protected $fillable = [
        'character_id',
        'skill_type',
        'skill_name',
        'level',
        'experience',
        'effects',
        'sp_cost',
        'duration',
        'is_active',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'level' => 'integer',
        'experience' => 'integer',
        'effects' => 'array',
        'sp_cost' => 'integer',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function getSkillEffects(): array
    {
        if (!$this->is_active) {
            return [];
        }

        $baseEffects = $this->effects ?? [];
        $levelMultiplier = $this->level;

        $scaledEffects = [];
        foreach ($baseEffects as $effect => $value) {
            if (is_numeric($value)) {
                $scaledEffects[$effect] = $value * $levelMultiplier;
            } else {
                $scaledEffects[$effect] = $value;
            }
        }

        return $scaledEffects;
    }

    public function getMovementEffects(): array
    {
        $effects = $this->getSkillEffects();
        
        return [
            'dice_bonus' => $effects['dice_bonus'] ?? 0,
            'extra_dice' => $effects['extra_dice'] ?? 0,
            'movement_multiplier' => $effects['movement_multiplier'] ?? 1.0,
            'special_effects' => array_filter($effects, function($key) {
                return !in_array($key, ['dice_bonus', 'extra_dice', 'movement_multiplier']);
            }, ARRAY_FILTER_USE_KEY),
        ];
    }

    public function getSkillSpCost(): int
    {
        $baseCost = $this->sp_cost ?? 10;
        
        if ($this->skill_type === 'gathering') {
            $levelReduction = max(0, floor($this->level / 2));
            return max(3, $baseCost - $levelReduction);
        }
        
        $levelReduction = max(0, floor($this->level / 3));
        return max(1, $baseCost - $levelReduction);
    }

    public function calculateExperienceGain(): int
    {
        $baseExp = 15;
        $levelBonus = $this->level * 2;
        return $baseExp + $levelBonus;
    }

    public function applySkillEffect(int $characterId): array
    {
        if ($this->skill_name === '飛脚術') {
            return $this->applyHikyakuEffect($characterId);
        }

        if ($this->skill_type === 'gathering') {
            return $this->applyGatheringEffect($characterId);
        }

        return ['success' => false, 'message' => 'スキル効果が実装されていません。'];
    }

    private function applyHikyakuEffect(int $characterId): array
    {
        $effectName = '飛脚術効果';
        $duration = $this->duration ?? 5;
        
        $existingEffect = ActiveEffect::where('character_id', $characterId)
                                    ->where('effect_name', $effectName)
                                    ->where('is_active', true)
                                    ->first();

        if ($existingEffect) {
            $existingEffect->remaining_duration = max($existingEffect->remaining_duration, $duration);
            $existingEffect->save();
            
            return [
                'success' => true,
                'message' => '飛脚術効果の持続時間が延長されました。',
                'duration' => $existingEffect->remaining_duration
            ];
        }

        $effectPower = $this->level;
        $effects = [
            'dice_bonus' => $effectPower,
            'extra_dice' => floor($effectPower / 3),
        ];

        ActiveEffect::createTemporaryEffect(
            $characterId,
            $effectName,
            $effects,
            $duration,
            'skill',
            $this->id
        );

        return [
            'success' => true,
            'message' => '飛脚術効果が発動しました。',
            'effects' => $effects,
            'duration' => $duration
        ];
    }

    private function applyGatheringEffect(int $characterId): array
    {
        $character = Character::find($characterId);
        $player = Player::where('character_id', $characterId)->first();
        
        if (!$player || !$player->isOnRoad()) {
            return ['success' => false, 'message' => '道にいる時のみ採集できます。'];
        }

        $roadId = $player->current_location_id;
        $gatheringTable = GatheringTable::getAvailableItems($roadId, $this->level);
        
        if (empty($gatheringTable)) {
            return ['success' => false, 'message' => 'この道では何も採集できません。'];
        }

        $selectedItem = $gatheringTable[array_rand($gatheringTable)];
        $result = GatheringTable::rollForItem($selectedItem);
        
        if (!$result['success']) {
            return ['success' => false, 'message' => '採集に失敗しました。'];
        }

        $inventory = $character->getInventory();
        $inventory->addItem($result['item'], $result['quantity']);
        
        return [
            'success' => true,
            'message' => "{$result['item']}を{$result['quantity']}個採集しました。",
            'item' => $result['item'],
            'quantity' => $result['quantity'],
            'rarity' => $result['rarity'],
        ];
    }

    public static function getSampleSkills(): array
    {
        return [
            [
                'skill_type' => 'movement',
                'skill_name' => '飛脚術',
                'description' => 'SP消費でサイコロボーナスと追加サイコロ効果を得る',
                'effects' => ['dice_bonus' => 1],
                'sp_cost' => 12,
                'duration' => 5,
                'max_level' => 10,
            ],
            [
                'skill_type' => 'movement',
                'skill_name' => '多重投擲',
                'description' => '複数のサイコロを同時に振る技術',
                'effects' => ['extra_dice' => 1],
                'sp_cost' => 8,
                'duration' => 3,
                'max_level' => 3,
            ],
            [
                'skill_type' => 'movement',
                'skill_name' => '長距離移動',
                'description' => '移動距離を倍化する技術',
                'effects' => ['movement_multiplier' => 0.2],
                'sp_cost' => 15,
                'duration' => 3,
                'max_level' => 5,
            ],
            [
                'skill_type' => 'combat',
                'skill_name' => '戦闘術',
                'description' => '戦闘能力を向上させる',
                'effects' => ['attack' => 2, 'defense' => 1],
                'sp_cost' => 10,
                'duration' => 10,
                'max_level' => 20,
            ],
            [
                'skill_type' => 'utility',
                'skill_name' => '状態異常耐性',
                'description' => '状態異常への耐性を向上',
                'effects' => ['status_resistance' => true],
                'sp_cost' => 20,
                'duration' => 20,
                'max_level' => 1,
            ],
            [
                'skill_type' => 'gathering',
                'skill_name' => '採集',
                'description' => '道で材料や薬草を採集する',
                'effects' => ['gathering_bonus' => 1],
                'sp_cost' => 8,
                'duration' => 0,
                'max_level' => 99,
            ],
        ];
    }

    public function canLevelUp(): bool
    {
        $requiredExp = $this->getRequiredExperienceForNextLevel();
        return $this->experience >= $requiredExp;
    }

    public function getRequiredExperienceForNextLevel(): int
    {
        if ($this->skill_type === 'gathering') {
            return ($this->level * $this->level * 50) + ($this->level * 100);
        }
        
        return ($this->level + 1) * 100;
    }

    public function gainExperience(int $amount): bool
    {
        $this->experience += $amount;
        
        $leveledUp = false;
        while ($this->canLevelUp()) {
            $this->experience -= $this->getRequiredExperienceForNextLevel();
            $this->level++;
            $leveledUp = true;
        }
        
        $this->save();
        
        // スキルレベルアップ時にキャラクターレベルも更新
        if ($leveledUp) {
            $character = $this->character;
            if ($character) {
                $character->updateCharacterLevel();
            }
        }
        
        return $leveledUp;
    }

    public static function createForCharacter(int $characterId, string $skillType, string $skillName, array $effects = [], int $spCost = 10, int $duration = 5): self
    {
        $skill = self::create([
            'character_id' => $characterId,
            'skill_type' => $skillType,
            'skill_name' => $skillName,
            'level' => 1,
            'experience' => 0,
            'effects' => $effects,
            'sp_cost' => $spCost,
            'duration' => $duration,
            'is_active' => true,
        ]);
        
        // スキル追加時にキャラクターレベルも更新
        $character = Character::find($characterId);
        if ($character) {
            $character->updateCharacterLevel();
        }
        
        return $skill;
    }
}