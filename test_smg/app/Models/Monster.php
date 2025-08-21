<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'level',
        'hp',
        'max_hp',
        'attack',
        'defense',
        'agility',
        'evasion',
        'accuracy',
        'experience_reward',
        'emoji',
        'description',
        'is_active',
    ];

    protected $casts = [
        'level' => 'integer',
        'hp' => 'integer',
        'max_hp' => 'integer',
        'attack' => 'integer',
        'defense' => 'integer',
        'agility' => 'integer',
        'evasion' => 'integer',
        'accuracy' => 'integer',
        'experience_reward' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function monsterSpawns()
    {
        return $this->hasMany(MonsterSpawn::class, 'monster_id');
    }

    public function spawnLists()
    {
        return $this->belongsToMany(SpawnList::class, 'monster_spawns', 'monster_id', 'spawn_list_id')
                    ->withPivot(['spawn_rate', 'priority', 'min_level', 'max_level', 'is_active'])
                    ->withTimestamps();
    }

    public function getHpPercentage(): float
    {
        if ($this->max_hp <= 0) {
            return 0;
        }
        return ($this->hp / $this->max_hp) * 100;
    }

    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    public function takeDamage(int $damage): void
    {
        $this->hp = max(0, $this->hp - $damage);
    }

    public function getMonsterInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'hp' => $this->hp,
            'max_hp' => $this->max_hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'agility' => $this->agility,
            'evasion' => $this->evasion,
            'accuracy' => $this->accuracy,
            'experience_reward' => $this->experience_reward,
            'emoji' => $this->emoji,
            'description' => $this->description,
            'hp_percentage' => $this->getHpPercentage(),
            'is_alive' => $this->isAlive(),
        ];
    }

    public static function getDummyMonsters(): array
    {
        // JSONベースシステム移行後の緊急フォールバック用データ
        // 注意: このデータは緊急時のみ使用され、通常はconfig/monsters/monsters.jsonから読み込まれます
        return [
            // 基本モンスター（全pathway共通フォールバック）
            [
                'id' => 'slime',
                'name' => 'スライム',
                'level' => 1,
                'hp' => 25,
                'max_hp' => 25,
                'attack' => 8,
                'defense' => 3,
                'agility' => 5,
                'evasion' => 10,
                'accuracy' => 85,
                'experience_reward' => 15,
                'emoji' => '🟢',
                'description' => '弱いが数が多い基本的なモンスター',
                'is_active' => true,
            ],
            [
                'id' => 'goblin',
                'name' => 'ゴブリン',
                'level' => 2,
                'hp' => 35,
                'max_hp' => 35,
                'attack' => 12,
                'defense' => 5,
                'agility' => 8,
                'evasion' => 15,
                'accuracy' => 80,
                'experience_reward' => 25,
                'emoji' => '👹',
                'description' => '小さいが狡猾な緑の魔物',
                'is_active' => true,
            ],
            [
                'id' => 'wolf',
                'name' => 'ウルフ',
                'level' => 3,
                'hp' => 45,
                'max_hp' => 45,
                'attack' => 15,
                'defense' => 8,
                'agility' => 12,
                'evasion' => 20,
                'accuracy' => 90,
                'experience_reward' => 35,
                'emoji' => '🐺',
                'description' => '素早い野生の狼',
                'is_active' => true,
            ],
        ];
    }

    public static function getRandomMonsterForRoad(string $roadId): ?array
    {
        // 新しいJSONベースシステムを使用
        $monsterConfigService = app(\App\Services\Monster\MonsterConfigService::class);
        $monster = $monsterConfigService->getRandomMonsterForPathway($roadId);
        
        if ($monster) {
            // データ完全性チェックと修正
            $monster = self::validateAndFixMonsterData($monster);
            
            \Log::debug('Monster selected for encounter via JSON config', [
                'pathway_id' => $roadId,
                'monster_name' => $monster['name'],
                'monster_data' => $monster
            ]);
            
            return $monster;
        }

        // 緊急フォールバック: 基本モンスターを返す（後方互換性）
        \Log::warning('Falling back to emergency dummy data for monster selection', [
            'pathway_id' => $roadId,
            'reason' => 'JSON config system failed'
        ]);
        
        $monsters = self::getDummyMonsters();
        
        if (empty($monsters)) {
            \Log::error('Emergency fallback: No dummy monsters available', ['pathway_id' => $roadId]);
            return null;
        }

        // 最初の利用可能なモンスターを返す（JSON設定がロードできない緊急時のみ）
        $firstMonster = reset($monsters);
        $firstMonster = self::validateAndFixMonsterData($firstMonster);
        
        \Log::info('Emergency fallback monster selected', [
            'pathway_id' => $roadId,
            'monster_name' => $firstMonster['name'],
            'message' => 'Please check JSON monster configuration files'
        ]);
        
        return $firstMonster;
    }

    /**
     * モンスターデータの完全性チェックと自動修正
     * 
     * @param array $monster
     * @return array
     */
    private static function validateAndFixMonsterData(array $monster): array
    {
        $requiredFields = [
            'name' => 'Unknown Monster',
            'level' => 1,
            'hp' => 100,
            'max_hp' => 100,
            'attack' => 15,
            'defense' => 10,
            'agility' => 10,
            'evasion' => 10,
            'accuracy' => 80,
            'experience_reward' => 0,
            'emoji' => '👹',
            'description' => '',
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $defaultValue) {
            if (!isset($monster[$field]) || $monster[$field] === null) {
                $monster[$field] = $defaultValue;
                $missingFields[] = $field;
            }
        }

        // 特別なケース: descriptionが空の場合はデフォルト値を生成
        if (empty($monster['description'])) {
            $monster['description'] = "レベル{$monster['level']}の{$monster['name']}";
        }

        // HP系の整合性チェック
        if ($monster['hp'] > $monster['max_hp']) {
            $monster['hp'] = $monster['max_hp'];
        }

        // 負の値チェック
        $numericFields = ['level', 'hp', 'max_hp', 'attack', 'defense', 'agility', 'evasion', 'accuracy', 'experience_reward'];
        foreach ($numericFields as $field) {
            if ($monster[$field] < 0) {
                $monster[$field] = $requiredFields[$field];
                $missingFields[] = $field . '(negative_value)';
            }
        }

        if (!empty($missingFields)) {
            \Log::warning('Monster data validation: Fixed missing/invalid fields', [
                'monster_name' => $monster['name'],
                'fixed_fields' => $missingFields,
                'monster_data' => $monster
            ]);
        }

        return $monster;
    }
}