<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Character extends Model
{
    protected $fillable = [
        'user_id',
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
        'experience',
        'experience_to_next',
        'location_type',
        'location_id',
        'game_position',
        'last_visited_town',
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

    // キャッシュ用プロパティ
    private $_skillBonusesCache = null;

    protected $casts = [
        'user_id' => 'integer',
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
        'experience' => 'integer',
        'experience_to_next' => 'integer',
        'game_position' => 'integer',
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

    public static function createNewCharacter(int $userId, string $name = '冒険者'): self
    {
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'level' => 1,
            'experience' => 0,
            'experience_to_next' => 100,
            'attack' => 15,
            'magic_attack' => 12,
            'defense' => 12,
            'agility' => 18,
            'evasion' => 22,
            'hp' => 85,
            'max_hp' => 120,
            'sp' => 30,
            'max_sp' => 60,
            'mp' => 45,
            'max_mp' => 80,
            'accuracy' => 90,
            'gold' => 500,
            'location_type' => 'town',
            'location_id' => 'town_a',
            'game_position' => 0,
            'last_visited_town' => 'town_a',
        ]);
    }

    // ユーザーのキャラクターを取得または作成
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'name' => '冒険者',
                'level' => 1,
                'experience' => 0,
                'experience_to_next' => 100,
                'attack' => 15,
                'magic_attack' => 12,
                'defense' => 12,
                'agility' => 18,
                'evasion' => 22,
                'hp' => 85,
                'max_hp' => 120,
                'sp' => 30,
                'max_sp' => 60,
                'mp' => 45,
                'max_mp' => 80,
                'accuracy' => 90,
                'gold' => 500,
                'location_type' => 'town',
                'location_id' => 'town_a',
                'game_position' => 0,
                'last_visited_town' => 'town_a',
            ]
        );
    }

    // ゲーム進行状況の更新
    public function updateLocation(string $locationType, string $locationId, int $gamePosition = 0): void
    {
        $this->update([
            'location_type' => $locationType,
            'location_id' => $locationId,
            'game_position' => $gamePosition,
        ]);

        // 町に入った場合は履歴を更新
        if ($locationType === 'town') {
            $this->update(['last_visited_town' => $locationId]);
        }
    }

    // 経験値獲得とレベルアップ
    public function gainExperience(int $experience): array
    {
        $this->experience += $experience;
        $leveledUp = false;
        $newLevel = $this->level;

        while ($this->experience >= $this->experience_to_next) {
            $this->experience -= $this->experience_to_next;
            $newLevel++;
            $leveledUp = true;
            
            // 次のレベルまでの経験値を設定（レベル * 100）
            $this->experience_to_next = $newLevel * 100;
            
            // レベルアップ時のステータス上昇
            $this->levelUpStats();
        }

        if ($leveledUp) {
            $this->level = $newLevel;
            $this->save();
        } else {
            $this->save();
        }

        return [
            'leveled_up' => $leveledUp,
            'new_level' => $newLevel,
            'experience_gained' => $experience,
        ];
    }

    // レベルアップ時のステータス上昇
    private function levelUpStats(): void
    {
        $this->max_hp += rand(8, 12);
        $this->max_mp += rand(3, 7);
        $this->max_sp += rand(2, 5);
        $this->attack += rand(1, 3);
        $this->magic_attack += rand(1, 2);
        $this->defense += rand(1, 2);
        $this->agility += rand(1, 2);
        
        // HP/MP/SPを最大値まで回復
        $this->hp = $this->max_hp;
        $this->mp = $this->max_mp;
        $this->sp = $this->max_sp;
    }


    // リレーション
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function battleLogs(): HasMany
    {
        return $this->hasMany(BattleLog::class, 'user_id', 'user_id');
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
        $skill = $this->getSkill($skillName);
        
        if (!$skill) {
            return ['success' => false, 'message' => "スキル「{$skillName}」を習得していません。"];
        }
        
        if (!$skill->is_active) {
            return ['success' => false, 'message' => "スキル「{$skillName}」は無効化されています。"];
        }
        
        $spCost = $skill->getSkillSpCost();
        if (!$this->consumeSP($spCost)) {
            return ['success' => false, 'message' => "SPが足りません。必要SP: {$spCost}"];
        }
        
        // スキル効果を適用
        $result = $skill->applySkillEffect($this->id);
        
        if ($result['success']) {
            // スキル経験値を獲得
            $expGain = $skill->calculateExperienceGain();
            $leveledUp = $skill->gainExperience($expGain);
            
            $result['experience_gained'] = $expGain;
            $result['skill_leveled_up'] = $leveledUp;
            
            $this->save(); // SP消費を保存
        }
        
        return $result;
    }

    public function getSkill(string $skillName): ?Skill
    {
        return $this->skills()->where('skill_name', $skillName)->first();
    }

    public function hasSkill(string $skillName): bool
    {
        return $this->skills()->where('skill_name', $skillName)->exists();
    }

    public function getActiveSkills(): array
    {
        return $this->skills()->where('is_active', true)->get()->toArray();
    }

    // スキル学習メソッド
    public function learnSkill(string $skillType, string $skillName, array $effects = [], int $spCost = 10, int $duration = 5): Skill
    {
        // 既に習得している場合はそのスキルを返す
        $existingSkill = $this->getSkill($skillName);
        if ($existingSkill) {
            return $existingSkill;
        }

        // 新しいスキルを作成
        $skill = Skill::createForCharacter($this->id, $skillType, $skillName, $effects, $spCost, $duration);
        
        // スキル追加後にキャラクターレベルを更新
        $this->updateCharacterLevel();
        
        return $skill;
    }

    // スキル一覧を取得
    public function getSkillList(): array
    {
        return $this->skills()->get()->map(function($skill) {
            return [
                'id' => $skill->id,
                'skill_name' => $skill->skill_name,
                'skill_type' => $skill->skill_type,
                'level' => $skill->level,
                'experience' => $skill->experience,
                'required_exp' => $skill->getRequiredExperienceForNextLevel(),
                'sp_cost' => $skill->getSkillSpCost(),
                'is_active' => $skill->is_active,
                'effects' => $skill->effects,
            ];
        })->toArray();
    }

    // 総スキルレベルを取得
    public function getTotalSkillLevel(): int
    {
        return $this->skills()->sum('level');
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
        // スキルレベルの合計からキャラクターレベルを計算
        $totalSkillLevel = $this->skills()->sum('level');
        
        // スキルレベル合計を基にキャラクターレベルを計算
        // 例: スキルレベル合計10でキャラクターレベル2、20で3、など
        if ($totalSkillLevel == 0) {
            return 1; // 初期レベル
        }
        
        return max(1, floor($totalSkillLevel / 10) + 1);
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
        // キャッシュがある場合はそれを使用
        $cacheKey = 'skill_bonuses_' . $this->id;
        if (isset($this->_skillBonusesCache)) {
            return $this->_skillBonusesCache;
        }

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

        // スキルデータを取得（既にeager loadされていればそれを使用）
        $skills = $this->relationLoaded('skills') 
            ? $this->skills->where('is_active', true)
            : $this->skills()->where('is_active', true)->get();
        
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
        
        // キャッシュに保存
        $this->_skillBonusesCache = $bonuses;
        
        return $bonuses;
    }

    // スキルボーナスキャッシュを無効化
    public function clearSkillBonusesCache(): void
    {
        $this->_skillBonusesCache = null;
    }

    public function getDetailedStatsWithLevel(): array
    {
        $baseStats = $this->getDetailedStats();
        $skillBonuses = $this->calculateSkillBonuses();
        $totalSkillLevel = $this->getTotalSkillLevel();
        
        return array_merge($baseStats, [
            'character_level' => $this->level ?? 1,
            'total_skill_level' => $totalSkillLevel,
            'skill_bonuses' => $skillBonuses,
            'base_stats' => $this->getBaseStats(),
        ]);
    }

    // Equipment関連
    public function getOrCreateEquipment(): Equipment
    {
        return $this->equipment ?: Equipment::createForCharacter($this->id);
    }

    // 装備を含む総合ステータス取得
    public function getTotalStatsWithEquipment(): array
    {
        $baseStats = $this->getDetailedStats();
        $equipment = $this->getOrCreateEquipment();
        $equipmentStats = $equipment->getTotalStats();
        
        // 基本ステータスと装備ステータスを合計
        return [
            'attack' => ($baseStats['attack'] ?? 0) + ($equipmentStats['attack'] ?? 0),
            'defense' => ($baseStats['defense'] ?? 0) + ($equipmentStats['defense'] ?? 0),
            'agility' => ($baseStats['agility'] ?? 0) + ($equipmentStats['agility'] ?? 0),
            'evasion' => ($baseStats['evasion'] ?? 0) + ($equipmentStats['evasion'] ?? 0),
            'hp' => $baseStats['hp'] ?? 0,
            'max_hp' => ($baseStats['max_hp'] ?? 0) + ($equipmentStats['hp'] ?? 0),
            'mp' => $baseStats['mp'] ?? 0,
            'max_mp' => ($baseStats['max_mp'] ?? 0) + ($equipmentStats['mp'] ?? 0),
            'accuracy' => ($baseStats['accuracy'] ?? 0) + ($equipmentStats['accuracy'] ?? 0),
            'equipment_effects' => $equipmentStats['effects'] ?? [],
        ];
    }

    // 戦闘用の最適化されたステータス取得
    public function getBattleStats(): array
    {
        // スキルをeager loadして効率化
        if (!$this->relationLoaded('skills')) {
            $this->load('skills');
        }
        
        $totalStats = $this->getTotalStatsWithEquipment();
        $skillBonuses = $this->calculateSkillBonuses();
        
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'プレイヤー',
            'level' => $this->level ?? 1,
            'hp' => $this->hp ?? 100,
            'max_hp' => $totalStats['max_hp'],
            'mp' => $this->mp ?? 50,
            'max_mp' => $totalStats['max_mp'],
            'sp' => $this->sp ?? 100,
            'max_sp' => $this->max_sp ?? 100,
            'attack' => $totalStats['attack'] + ($skillBonuses['attack'] ?? 0),
            'defense' => $totalStats['defense'] + ($skillBonuses['defense'] ?? 0),
            'agility' => $totalStats['agility'] + ($skillBonuses['agility'] ?? 0),
            'evasion' => $totalStats['evasion'] + ($skillBonuses['evasion'] ?? 0),
            'accuracy' => $totalStats['accuracy'] + ($skillBonuses['accuracy'] ?? 0),
            'gold' => $this->gold ?? 500,
        ];
    }
}