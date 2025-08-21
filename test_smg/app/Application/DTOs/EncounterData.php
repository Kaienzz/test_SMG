<?php

namespace App\Application\DTOs;

use App\Models\Monster;

/**
 * エンカウントデータDTO
 */
class EncounterData
{
    public function __construct(
        public readonly string|int $monster_id,
        public readonly string $name,
        public readonly string $emoji,
        public readonly int $level,
        public readonly array $stats,
        public readonly string $description,
        public readonly string $encounter_type = 'battle'
    ) {}

    /**
     * Monster モデルから EncounterData を作成
     *
     * @param Monster $monster
     * @return self
     */
    public static function fromMonster(Monster $monster): self
    {
        return new self(
            monster_id: $monster->id,
            name: $monster->name,
            emoji: $monster->emoji ?? '👹',
            level: $monster->level ?? 1,
            stats: [
                'hp' => $monster->hp ?? 100,
                'max_hp' => $monster->max_hp ?? 100,
                'attack' => $monster->attack ?? 15,
                'defense' => $monster->defense ?? 10,
                'agility' => $monster->agility ?? 10,
                'evasion' => $monster->evasion ?? 10,
                'accuracy' => $monster->accuracy ?? 80,
                'experience_reward' => $monster->experience_reward ?? 0,
            ],
            description: $monster->description ?? 'モンスターの説明はありません'
        );
    }

    /**
     * 配列から EncounterData を作成
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        // モンスター情報からstats配列を構築
        $stats = $data['stats'] ?? [
            'hp' => $data['hp'] ?? 100,
            'max_hp' => $data['max_hp'] ?? 100,
            'attack' => $data['attack'] ?? 15,
            'defense' => $data['defense'] ?? 10,
            'agility' => $data['agility'] ?? 10,
            'evasion' => $data['evasion'] ?? 10,
            'accuracy' => $data['accuracy'] ?? 80,
            'experience_reward' => $data['experience_reward'] ?? 0,
        ];
        
        return new self(
            monster_id: $data['id'] ?? $data['monster_id'] ?? 0,
            name: $data['name'] ?? 'Unknown Monster',
            emoji: $data['emoji'] ?? '👹',
            level: $data['level'] ?? 1,
            stats: $stats,
            description: $data['description'] ?? 'モンスターの説明はありません',
            encounter_type: $data['encounter_type'] ?? 'battle'
        );
    }

    /**
     * 配列に変換（ネスト構造）
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->monster_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'level' => $this->level,
            'stats' => $this->stats,
            'description' => $this->description,
            'encounter_type' => $this->encounter_type,
        ];
    }

    /**
     * フラット配列に変換（レガシー互換性用）
     *
     * @return array
     */
    public function toFlatArray(): array
    {
        return [
            'id' => $this->monster_id,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'level' => $this->level,
            'hp' => $this->stats['hp'] ?? 100,
            'max_hp' => $this->stats['max_hp'] ?? 100,
            'attack' => $this->stats['attack'] ?? 15,
            'defense' => $this->stats['defense'] ?? 10,
            'agility' => $this->stats['agility'] ?? 10,
            'evasion' => $this->stats['evasion'] ?? 10,
            'accuracy' => $this->stats['accuracy'] ?? 80,
            'experience_reward' => $this->stats['experience_reward'] ?? 0,
            'description' => $this->description,
            'encounter_type' => $this->encounter_type,
        ];
    }
}