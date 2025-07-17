<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = [
        'name',
        'experience',
        'attack',
        'defense',
        'agility',
        'evasion',
        'hp',
        'max_hp',
        'sp',
        'max_sp',
        'mp',
        'max_mp',
        'magic_attack',
        'accuracy',
        'gold',
    ];

    protected $casts = [
        'experience' => 'integer',
        'attack' => 'integer',
        'defense' => 'integer',
        'agility' => 'integer',
        'evasion' => 'integer',
        'hp' => 'integer',
        'max_hp' => 'integer',
        'sp' => 'integer',
        'max_sp' => 'integer',
        'mp' => 'integer',
        'max_mp' => 'integer',
        'magic_attack' => 'integer',
        'accuracy' => 'integer',
        'gold' => 'integer',
    ];

    public function getHpPercentage(): float
    {
        if ($this->max_hp <= 0) {
            return 0;
        }
        return ($this->hp / $this->max_hp) * 100;
    }

    public function getSpPercentage(): float
    {
        if ($this->max_sp <= 0) {
            return 0;
        }
        return ($this->sp / $this->max_sp) * 100;
    }

    public function getMpPercentage(): float
    {
        if ($this->max_mp <= 0) {
            return 0;
        }
        return ($this->mp / $this->max_mp) * 100;
    }

    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    public function takeDamage(int $damage): void
    {
        $this->hp = max(0, $this->hp - $damage);
    }

    public function heal(int $amount): void
    {
        $this->hp = min($this->max_hp, $this->hp + $amount);
    }

    public function consumeSP(int $amount): bool
    {
        if ($this->sp < $amount) {
            return false;
        }
        $this->sp -= $amount;
        return true;
    }

    public function restoreSP(int $amount): void
    {
        $this->sp = min($this->max_sp, $this->sp + $amount);
    }

    public function consumeMP(int $amount): bool
    {
        if ($this->mp < $amount) {
            return false;
        }
        $this->mp -= $amount;
        return true;
    }

    public function restoreMP(int $amount): void
    {
        $this->mp = min($this->max_mp, $this->mp + $amount);
    }

    public function getStatusSummary(): array
    {
        return [
            'name' => $this->name,
            'hp' => "{$this->hp}/{$this->max_hp}",
            'sp' => "{$this->sp}/{$this->max_sp}",
            'mp' => "{$this->mp}/{$this->max_mp}",
            'hp_percentage' => $this->getHpPercentage(),
            'sp_percentage' => $this->getSpPercentage(),
            'mp_percentage' => $this->getMpPercentage(),
            'is_alive' => $this->isAlive(),
        ];
    }

    public function getDetailedStats(): array
    {
        return [
            'basic_info' => [
                'name' => $this->name,
                'experience' => $this->experience,
            ],
            'combat_stats' => [
                'attack' => $this->attack,
                'magic_attack' => $this->magic_attack,
                'defense' => $this->defense,
                'agility' => $this->agility,
                'evasion' => $this->evasion,
                'accuracy' => $this->accuracy,
            ],
            'vitals' => [
                'hp' => $this->hp,
                'max_hp' => $this->max_hp,
                'sp' => $this->sp,
                'max_sp' => $this->max_sp,
                'mp' => $this->mp,
                'max_mp' => $this->max_mp,
                'hp_percentage' => $this->getHpPercentage(),
                'sp_percentage' => $this->getSpPercentage(),
                'mp_percentage' => $this->getMpPercentage(),
            ],
        ];
    }

    public static function createNewCharacter(string $name): self
    {
        return new self([
            'name' => $name,
            'experience' => 0,
            'attack' => 10,
            'magic_attack' => 8,
            'defense' => 8,
            'agility' => 12,
            'evasion' => 15,
            'hp' => 100,
            'max_hp' => 100,
            'sp' => 50,
            'max_sp' => 50,
            'mp' => 30,
            'max_mp' => 30,
            'accuracy' => 85,
            'gold' => 1000,
        ]);
    }

    public function gainExperience(int $amount): void
    {
        $this->experience += $amount;
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function equipment(): HasOne
    {
        return $this->hasOne(Equipment::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function activeEffects(): HasMany
    {
        return $this->hasMany(ActiveEffect::class);
    }

    public function getInventory(): Inventory
    {
        if ($this->inventory) {
            return $this->inventory;
        }
        
        return Inventory::createForCharacter($this->id ?? 1);
    }

    public function getEquipment(): Equipment
    {
        if ($this->equipment) {
            return $this->equipment;
        }
        
        return Equipment::createForCharacter($this->id ?? 1);
    }

    public function getCharacterWithInventory(): array
    {
        $inventory = $this->getInventory();
        
        return [
            'character' => $this->getDetailedStats(),
            'inventory' => $inventory->getInventoryData(),
        ];
    }

    public function getCharacterWithEquipment(): array
    {
        $inventory = $this->getInventory();
        $equipment = $this->getEquipment();
        
        return [
            'character' => $this->getDetailedStats(),
            'inventory' => $inventory->getInventoryData(),
            'equipment' => $equipment->getEquippedItems(),
            'equipment_stats' => $equipment->getTotalStats(),
        ];
    }

    public function useSkill(string $skillName): array
    {
        $skill = $this->skills()->where('skill_name', $skillName)->where('is_active', true)->first();
        
        if (!$skill) {
            return ['success' => false, 'message' => "スキル「{$skillName}」が見つからないか、無効になっています。"];
        }

        $spCost = $skill->getSkillSpCost();
        
        if (!$this->consumeSP($spCost)) {
            return ['success' => false, 'message' => "SPが足りません。必要SP: {$spCost}"];
        }

        $this->save();

        $experienceGained = $skill->calculateExperienceGain();
        $leveledUp = $skill->gainExperience($experienceGained);

        $effectResult = $skill->applySkillEffect($this->id);

        return [
            'success' => true,
            'message' => "スキル「{$skillName}」を使用しました。",
            'sp_consumed' => $spCost,
            'remaining_sp' => $this->sp,
            'experience_gained' => $experienceGained,
            'leveled_up' => $leveledUp,
            'effect_applied' => $effectResult,
            'skill_level' => $skill->level,
        ];
    }

    public function getSkill(string $skillName): ?Skill
    {
        return $this->skills()->where('skill_name', $skillName)->where('is_active', true)->first();
    }

    public function hasSkill(string $skillName): bool
    {
        return $this->getSkill($skillName) !== null;
    }

    public function getActiveSkills(): array
    {
        return $this->skills()->where('is_active', true)->get()->map(function($skill) {
            return [
                'id' => $skill->id,
                'name' => $skill->skill_name,
                'type' => $skill->skill_type,
                'level' => $skill->level,
                'experience' => $skill->experience,
                'sp_cost' => $skill->getSkillSpCost(),
                'can_use' => $this->sp >= $skill->getSkillSpCost(),
                'effects' => $skill->getSkillEffects(),
            ];
        })->toArray();
    }

    public function spendGold(int $amount): bool
    {
        if ($this->gold >= $amount) {
            $this->gold -= $amount;
            return true;
        }
        return false;
    }

    public function addGold(int $amount): void
    {
        $this->gold += $amount;
    }

    public function hasGold(int $amount): bool
    {
        return $this->gold >= $amount;
    }
}