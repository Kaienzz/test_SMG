<?php

namespace App\Application\DTOs;

/**
 * 戦闘専用モンスターデータDTO
 * 
 * EncounterDataとは異なり、戦闘に特化したデータ構造を提供
 * UI表示用と戦闘計算用の明確な分離
 */
class BattleMonsterData
{
    public function __construct(
        public readonly int $monster_id,
        public readonly string $name,
        public readonly string $emoji,
        public readonly int $level,
        public readonly string $description,
        public readonly int $hp,
        public readonly int $max_hp,
        public readonly int $attack,
        public readonly int $defense,
        public readonly int $agility,
        public readonly int $evasion,
        public readonly int $accuracy,
        public readonly int $experience_reward,
        public readonly string $encounter_type = 'battle'
    ) {}

    /**
     * EncounterDataから戦闘用データに変換
     *
     * @param EncounterData $encounterData
     * @return self
     */
    public static function fromEncounterData(EncounterData $encounterData): self
    {
        $stats = $encounterData->stats;
        
        return new self(
            monster_id: $encounterData->monster_id,
            name: $encounterData->name,
            emoji: $encounterData->emoji,
            level: $encounterData->level,
            description: $encounterData->description,
            hp: $stats['hp'] ?? 100,
            max_hp: $stats['max_hp'] ?? 100,
            attack: $stats['attack'] ?? 15,
            defense: $stats['defense'] ?? 10,
            agility: $stats['agility'] ?? 10,
            evasion: $stats['evasion'] ?? 10,
            accuracy: $stats['accuracy'] ?? 80,
            experience_reward: $stats['experience_reward'] ?? 0,
            encounter_type: $encounterData->encounter_type
        );
    }

    /**
     * 配列からBattleMonsterDataを作成
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        // ネスト構造とフラット構造の両方に対応
        $stats = $data['stats'] ?? $data;
        
        return new self(
            monster_id: $data['id'] ?? 0,
            name: $data['name'] ?? 'Unknown Monster',
            emoji: $data['emoji'] ?? '👹',
            level: $data['level'] ?? 1,
            description: $data['description'] ?? 'モンスターの説明はありません',
            hp: $stats['hp'] ?? 100,
            max_hp: $stats['max_hp'] ?? 100,
            attack: $stats['attack'] ?? 15,
            defense: $stats['defense'] ?? 10,
            agility: $stats['agility'] ?? 10,
            evasion: $stats['evasion'] ?? 10,
            accuracy: $stats['accuracy'] ?? 80,
            experience_reward: $stats['experience_reward'] ?? 0,
            encounter_type: $data['encounter_type'] ?? 'battle'
        );
    }

    /**
     * UI表示用配列（ネスト構造）
     *
     * @return array
     */
    public function toUIArray(): array
    {
        return [
            'id' => $this->monster_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'level' => $this->level,
            'description' => $this->description,
            'stats' => [
                'hp' => $this->hp,
                'max_hp' => $this->max_hp,
                'attack' => $this->attack,
                'defense' => $this->defense,
                'agility' => $this->agility,
                'evasion' => $this->evasion,
                'accuracy' => $this->accuracy,
                'experience_reward' => $this->experience_reward,
            ],
            'encounter_type' => $this->encounter_type,
        ];
    }

    /**
     * 戦闘計算用配列（フラット構造）
     *
     * @return array
     */
    public function toBattleArray(): array
    {
        return [
            'id' => $this->monster_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'level' => $this->level,
            'description' => $this->description,
            'hp' => $this->hp,
            'max_hp' => $this->max_hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'agility' => $this->agility,
            'evasion' => $this->evasion,
            'accuracy' => $this->accuracy,
            'experience_reward' => $this->experience_reward,
            'encounter_type' => $this->encounter_type,
        ];
    }

    /**
     * 戦闘状態の更新
     *
     * @param int $newHp
     * @return self
     */
    public function withUpdatedHp(int $newHp): self
    {
        return new self(
            monster_id: $this->monster_id,
            name: $this->name,
            emoji: $this->emoji,
            level: $this->level,
            description: $this->description,
            hp: max(0, min($newHp, $this->max_hp)),
            max_hp: $this->max_hp,
            attack: $this->attack,
            defense: $this->defense,
            agility: $this->agility,
            evasion: $this->evasion,
            accuracy: $this->accuracy,
            experience_reward: $this->experience_reward,
            encounter_type: $this->encounter_type
        );
    }

    /**
     * 生存状態チェック
     *
     * @return bool
     */
    public function isAlive(): bool
    {
        return $this->hp > 0;
    }

    /**
     * HPパーセンテージ取得
     *
     * @return float
     */
    public function getHpPercentage(): float
    {
        if ($this->max_hp <= 0) {
            return 0;
        }
        return ($this->hp / $this->max_hp) * 100;
    }

    /**
     * デバッグ用文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        return "BattleMonsterData[{$this->name} Lv.{$this->level} HP:{$this->hp}/{$this->max_hp}]";
    }
}