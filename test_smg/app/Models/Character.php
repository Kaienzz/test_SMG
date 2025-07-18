<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = [
        'name',
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
        'level',
        'base_attack',
        'base_defense',
        'base_agility',
        'base_evasion',
        'base_max_hp',
        'base_max_sp',
        'base_max_mp',
        'base_magic_attack',
        'base_accuracy',
    ];

    protected $casts = [
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
        'level' => 'integer',
        'base_attack' => 'integer',
        'base_defense' => 'integer',
        'base_agility' => 'integer',
        'base_evasion' => 'integer',
        'base_max_hp' => 'integer',
        'base_max_sp' => 'integer',
        'base_max_mp' => 'integer',
        'base_magic_attack' => 'integer',
        'base_accuracy' => 'integer',
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
            'level' => $this->level ?? 1,
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
                'level' => $this->level ?? 1,
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
            'level' => 1,
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
            'base_attack' => 10,
            'base_magic_attack' => 8,
            'base_defense' => 8,
            'base_agility' => 12,
            'base_evasion' => 15,
            'base_max_hp' => 100,
            'base_max_sp' => 50,
            'base_max_mp' => 30,
            'base_accuracy' => 85,
        ]);
    }


    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function equipment(): HasOne
    {
        return $this->hasOne(Equipment::class);
    }

    // スキルシステムが削除されたため、空のクエリビルダーを返す
    public function skills()
    {
        return $this->newQuery()->whereRaw('1 = 0'); // 常に空を返す
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
        // スキルシステムが削除されたため、このメソッドは無効化
        return ['success' => false, 'message' => "スキルシステムは現在利用できません。"];
    }

    public function getSkill(string $skillName): ?Skill
    {
        // スキルシステムが削除されたため、このメソッドは無効化
        return null;
    }

    public function hasSkill(string $skillName): bool
    {
        // スキルシステムが削除されたため、このメソッドは無効化
        return false;
    }

    public function getActiveSkills(): array
    {
        // スキルシステムが削除されたため、このメソッドは無効化
        return [];
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

    public function calculateCharacterLevel(): int
    {
        // スキルシステムが削除されたため、デフォルトレベルを返す
        return $this->level ?? 1;
    }

    public function updateCharacterLevel(): bool
    {
        $newLevel = $this->calculateCharacterLevel();
        $oldLevel = $this->level ?? 1;
        
        if ($newLevel !== $oldLevel) {
            $this->level = $newLevel;
            $this->updateStatsForLevel();
            $this->save();
            return true;
        }
        
        return false;
    }

    public function updateStatsForLevel(): void
    {
        $baseStats = $this->getBaseStats();
        $skillBonuses = $this->calculateSkillBonuses();
        
        // 基本ステータス + スキルボーナス
        $this->attack = $baseStats['attack'] + $skillBonuses['attack'];
        $this->defense = $baseStats['defense'] + $skillBonuses['defense'];
        $this->agility = $baseStats['agility'] + $skillBonuses['agility'];
        $this->evasion = $baseStats['evasion'] + $skillBonuses['evasion'];
        $this->magic_attack = $baseStats['magic_attack'] + $skillBonuses['magic_attack'];
        $this->accuracy = $baseStats['accuracy'] + $skillBonuses['accuracy'];
        
        // HP/SP/MPの最大値を更新
        $oldMaxHp = $this->max_hp;
        $oldMaxSp = $this->max_sp;
        $oldMaxMp = $this->max_mp;
        
        $this->max_hp = $baseStats['max_hp'] + $skillBonuses['max_hp'];
        $this->max_sp = $baseStats['max_sp'] + $skillBonuses['max_sp'];
        $this->max_mp = $baseStats['max_mp'] + $skillBonuses['max_mp'];
        
        // 現在値を比例して増加
        if ($oldMaxHp > 0) {
            $this->hp = min($this->max_hp, floor($this->hp * ($this->max_hp / $oldMaxHp)));
        }
        if ($oldMaxSp > 0) {
            $this->sp = min($this->max_sp, floor($this->sp * ($this->max_sp / $oldMaxSp)));
        }
        if ($oldMaxMp > 0) {
            $this->mp = min($this->max_mp, floor($this->mp * ($this->max_mp / $oldMaxMp)));
        }
    }

    private function getBaseStats(): array
    {
        return [
            'attack' => $this->base_attack ?? 10,
            'defense' => $this->base_defense ?? 8,
            'agility' => $this->base_agility ?? 12,
            'evasion' => $this->base_evasion ?? 15,
            'max_hp' => $this->base_max_hp ?? 100,
            'max_sp' => $this->base_max_sp ?? 50,
            'max_mp' => $this->base_max_mp ?? 30,
            'magic_attack' => $this->base_magic_attack ?? 8,
            'accuracy' => $this->base_accuracy ?? 85,
        ];
    }

    private function calculateSkillBonuses(): array
    {
        $bonuses = [
            'attack' => 0,
            'defense' => 0,
            'agility' => 0,
            'evasion' => 0,
            'max_hp' => 0,
            'max_sp' => 0,
            'max_mp' => 0,
            'magic_attack' => 0,
            'accuracy' => 0,
        ];

        // スキルシステムが削除されたため、空のコレクションを使用
        $skills = collect();
        
        foreach ($skills as $skill) {
            $skillLevel = $skill->level;
            $skillType = $skill->skill_type;
            
            // 全スキル共通のボーナス
            $bonuses['max_hp'] += $skillLevel * 2;
            $bonuses['max_sp'] += $skillLevel * 1;
            $bonuses['max_mp'] += $skillLevel * 1;
            
            // スキル種類別のボーナス
            switch ($skillType) {
                case 'combat':
                    $bonuses['attack'] += $skillLevel * 2;
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;
                    
                case 'movement':
                    $bonuses['agility'] += $skillLevel * 2;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
                    
                case 'gathering':
                    $bonuses['agility'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
                    
                case 'magic':
                    $bonuses['magic_attack'] += $skillLevel * 2;
                    $bonuses['accuracy'] += $skillLevel * 1;
                    break;
                    
                case 'utility':
                    $bonuses['defense'] += $skillLevel * 1;
                    $bonuses['evasion'] += $skillLevel * 1;
                    break;
            }
        }
        
        return $bonuses;
    }

    public function getDetailedStatsWithLevel(): array
    {
        $baseStats = $this->getDetailedStats();
        $skillBonuses = $this->calculateSkillBonuses();
        $totalSkillLevel = 0; // スキルシステムが削除されたため、0を返す
        
        return array_merge($baseStats, [
            'character_level' => $this->level ?? 1,
            'total_skill_level' => $totalSkillLevel,
            'skill_bonuses' => $skillBonuses,
            'base_stats' => $this->getBaseStats(),
        ]);
    }
}