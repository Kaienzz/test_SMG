<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monster extends Model
{
    protected $fillable = [
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
        'spawn_roads',
        'spawn_rate',
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
        'spawn_roads' => 'array',
        'spawn_rate' => 'float',
    ];

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
        return [
            // 道路1のモンスター
            [
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
                'spawn_roads' => ['road_1'],
                'spawn_rate' => 0.4,
            ],
            [
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
                'spawn_roads' => ['road_1'],
                'spawn_rate' => 0.3,
            ],
            [
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
                'spawn_roads' => ['road_1'],
                'spawn_rate' => 0.2,
            ],
            [
                'name' => 'オーク',
                'level' => 4,
                'hp' => 60,
                'max_hp' => 60,
                'attack' => 18,
                'defense' => 12,
                'agility' => 6,
                'evasion' => 8,
                'accuracy' => 75,
                'experience_reward' => 50,
                'emoji' => '👺',
                'description' => '力強い筋肉質の戦士',
                'spawn_roads' => ['road_1'],
                'spawn_rate' => 0.1,
            ],

            // 道路2のモンスター
            [
                'name' => 'ポイズンスパイダー',
                'level' => 4,
                'hp' => 40,
                'max_hp' => 40,
                'attack' => 16,
                'defense' => 6,
                'agility' => 15,
                'evasion' => 25,
                'accuracy' => 85,
                'experience_reward' => 45,
                'emoji' => '🕷️',
                'description' => '毒を持つ危険な蜘蛛',
                'spawn_roads' => ['road_2'],
                'spawn_rate' => 0.3,
            ],
            [
                'name' => 'スケルトン',
                'level' => 5,
                'hp' => 50,
                'max_hp' => 50,
                'attack' => 20,
                'defense' => 15,
                'agility' => 10,
                'evasion' => 12,
                'accuracy' => 80,
                'experience_reward' => 60,
                'emoji' => '💀',
                'description' => '骨だけの不死の戦士',
                'spawn_roads' => ['road_2'],
                'spawn_rate' => 0.4,
            ],
            [
                'name' => 'バンディット',
                'level' => 6,
                'hp' => 65,
                'max_hp' => 65,
                'attack' => 22,
                'defense' => 10,
                'agility' => 18,
                'evasion' => 22,
                'accuracy' => 88,
                'experience_reward' => 75,
                'emoji' => '🗡️',
                'description' => '道を荒らす盗賊',
                'spawn_roads' => ['road_2'],
                'spawn_rate' => 0.2,
            ],
            [
                'name' => 'トロール',
                'level' => 7,
                'hp' => 90,
                'max_hp' => 90,
                'attack' => 25,
                'defense' => 20,
                'agility' => 4,
                'evasion' => 5,
                'accuracy' => 70,
                'experience_reward' => 100,
                'emoji' => '👹',
                'description' => '巨大で強力だが鈍重な巨人',
                'spawn_roads' => ['road_2'],
                'spawn_rate' => 0.1,
            ],

            // 道路3のモンスター
            [
                'name' => 'ダークナイト',
                'level' => 8,
                'hp' => 85,
                'max_hp' => 85,
                'attack' => 28,
                'defense' => 25,
                'agility' => 12,
                'evasion' => 18,
                'accuracy' => 85,
                'experience_reward' => 120,
                'emoji' => '⚔️',
                'description' => '闇の力を持つ騎士',
                'spawn_roads' => ['road_3'],
                'spawn_rate' => 0.3,
            ],
            [
                'name' => 'ドラゴンリング',
                'level' => 9,
                'hp' => 70,
                'max_hp' => 70,
                'attack' => 30,
                'defense' => 18,
                'agility' => 20,
                'evasion' => 25,
                'accuracy' => 90,
                'experience_reward' => 150,
                'emoji' => '🐉',
                'description' => '小さなドラゴンの子供',
                'spawn_roads' => ['road_3'],
                'spawn_rate' => 0.2,
            ],
            [
                'name' => 'シャドウアサシン',
                'level' => 10,
                'hp' => 60,
                'max_hp' => 60,
                'attack' => 35,
                'defense' => 12,
                'agility' => 30,
                'evasion' => 40,
                'accuracy' => 95,
                'experience_reward' => 180,
                'emoji' => '🥷',
                'description' => '影から現れる暗殺者',
                'spawn_roads' => ['road_3'],
                'spawn_rate' => 0.3,
            ],
            [
                'name' => 'アンシェントビースト',
                'level' => 12,
                'hp' => 120,
                'max_hp' => 120,
                'attack' => 40,
                'defense' => 30,
                'agility' => 8,
                'evasion' => 10,
                'accuracy' => 80,
                'experience_reward' => 250,
                'emoji' => '🦁',
                'description' => '古代から生きる強大な獣',
                'spawn_roads' => ['road_3'],
                'spawn_rate' => 0.2,
            ],
        ];
    }

    public static function getRandomMonsterForRoad(string $roadId): ?array
    {
        $monsters = self::getDummyMonsters();
        $roadMonsters = array_filter($monsters, function($monster) use ($roadId) {
            return in_array($roadId, $monster['spawn_roads']);
        });

        if (empty($roadMonsters)) {
            return null;
        }

        // 確率に基づいてモンスターを選択
        $totalRate = array_sum(array_column($roadMonsters, 'spawn_rate'));
        $random = mt_rand() / mt_getrandmax();
        $cumulativeRate = 0;

        foreach ($roadMonsters as $monster) {
            $cumulativeRate += $monster['spawn_rate'] / $totalRate;
            if ($random <= $cumulativeRate) {
                return $monster;
            }
        }

        // フォールバック: 最初のモンスターを返す
        return reset($roadMonsters);
    }
}