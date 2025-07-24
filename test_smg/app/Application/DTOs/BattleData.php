<?php

namespace App\Application\DTOs;

use App\Models\Character;
use App\Models\Monster;
use App\Models\ActiveBattle;

/**
 * 戦闘用データ統一DTO
 * 
 * BattleController と GameDisplayService の戦闘関連データを型安全に管理
 * 戦闘開始、戦闘中、戦闘結果の各状態に対応
 */
class BattleData
{
    public function __construct(
        public readonly Character $character,
        public readonly CharacterBattleStats $characterStats,
        public readonly ?Monster $monster = null,
        public readonly ?MonsterBattleStats $monsterStats = null,
        public readonly ?ActiveBattle $activeBattle = null,
        public readonly array $availableSkills = [],
        public readonly BattleState $battleState = BattleState::NONE
    ) {}

    /**
     * 戦闘画面用データを作成
     *
     * @param Character $character
     * @return self
     */
    public static function forBattleView(Character $character): self
    {
        $characterStats = CharacterBattleStats::fromCharacter($character);
        $availableSkills = $character->getSkillList();

        return new self(
            character: $character,
            characterStats: $characterStats,
            availableSkills: $availableSkills,
            battleState: BattleState::READY
        );
    }

    /**
     * アクティブな戦闘データを作成
     *
     * @param ActiveBattle $activeBattle
     * @return self
     */
    public static function fromActiveBattle(ActiveBattle $activeBattle): self
    {
        $character = $activeBattle->character;
        $monster = $activeBattle->monster;
        
        $characterStats = CharacterBattleStats::fromCharacter($character);
        $monsterStats = MonsterBattleStats::fromMonster($monster);
        $availableSkills = $character->getSkillList();

        return new self(
            character: $character,
            characterStats: $characterStats,
            monster: $monster,
            monsterStats: $monsterStats,
            activeBattle: $activeBattle,
            availableSkills: $availableSkills,
            battleState: BattleState::ACTIVE
        );
    }

    /**
     * 戦闘開始データを作成
     *
     * @param Character $character
     * @param Monster $monster
     * @return self
     */
    public static function forBattleStart(Character $character, Monster $monster): self
    {
        $characterStats = CharacterBattleStats::fromCharacter($character);
        $monsterStats = MonsterBattleStats::fromMonster($monster);
        $availableSkills = $character->getSkillList();

        return new self(
            character: $character,
            characterStats: $characterStats,
            monster: $monster,
            monsterStats: $monsterStats,
            availableSkills: $availableSkills,
            battleState: BattleState::STARTING
        );
    }

    /**
     * Blade テンプレート用の配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'character' => $this->character,
            'stats' => $this->characterStats->toArray(),
            'status' => $this->character->getStatusSummary(),
            'skills' => $this->availableSkills,
            'battleState' => $this->battleState->value,
        ];

        if ($this->monster) {
            $result['monster'] = $this->monster;
            $result['monsterStats'] = $this->monsterStats?->toArray();
        }

        if ($this->activeBattle) {
            $result['activeBattle'] = [
                'id' => $this->activeBattle->id,
                'turn' => $this->activeBattle->turn,
                'battle_log' => $this->activeBattle->battle_log,
                'created_at' => $this->activeBattle->created_at,
            ];
        }

        return $result;
    }

    /**
     * JavaScript 用の JSON に変換
     *
     * @return array
     */
    public function toJson(): array
    {
        $result = [
            'character' => [
                'id' => $this->character->id,
                'name' => $this->character->name,
                'level' => $this->character->level,
            ],
            'characterStats' => $this->characterStats->toArray(),
            'skills' => array_map(fn($skill) => [
                'id' => $skill['id'] ?? null,
                'name' => $skill['skill_name'] ?? $skill['name'],
                'level' => $skill['level'] ?? 1,
                'sp_cost' => $skill['sp_cost'] ?? 0,
            ], $this->availableSkills),
            'battleState' => $this->battleState->value,
        ];

        if ($this->monster && $this->monsterStats) {
            $result['monster'] = [
                'id' => $this->monster->id,
                'name' => $this->monster->name,
                'emoji' => $this->monster->emoji ?? '👹',
                'level' => $this->monster->level ?? 1,
            ];
            $result['monsterStats'] = $this->monsterStats->toArray();
        }

        if ($this->activeBattle) {
            $result['battle'] = [
                'id' => $this->activeBattle->id,
                'turn' => $this->activeBattle->turn,
                'isActive' => true,
            ];
        }

        return $result;
    }

    /**
     * 戦闘が進行中かどうか
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->battleState === BattleState::ACTIVE && $this->activeBattle !== null;
    }

    /**
     * 戦闘開始可能かどうか
     *
     * @return bool
     */
    public function canStart(): bool
    {
        return $this->monster !== null && $this->battleState === BattleState::STARTING;
    }

    /**
     * キャラクターが生存しているかどうか
     *
     * @return bool
     */
    public function isCharacterAlive(): bool
    {
        return $this->character->isAlive();
    }

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        $vs = $this->monster ? " vs {$this->monster->name}" : '';
        return "BattleData[{$this->character->name}{$vs}, state={$this->battleState->value}]";
    }
}

/**
 * キャラクター戦闘統計DTO
 */
class CharacterBattleStats
{
    public function __construct(
        public readonly int $level,
        public readonly int $hp,
        public readonly int $max_hp,
        public readonly int $sp,
        public readonly int $max_sp,
        public readonly int $mp,
        public readonly int $max_mp,
        public readonly int $attack,
        public readonly int $defense,
        public readonly int $agility,
        public readonly int $evasion,
        public readonly int $accuracy,
        public readonly int $magic_attack,
        public readonly array $equipment_effects = []
    ) {}

    public static function fromCharacter(Character $character): self
    {
        $battleStats = $character->getBattleStats();
        
        return new self(
            level: $battleStats['level'],
            hp: $battleStats['hp'],
            max_hp: $battleStats['max_hp'],
            sp: $character->sp,
            max_sp: $character->max_sp,
            mp: $battleStats['mp'],
            max_mp: $battleStats['max_mp'],
            attack: $battleStats['attack'],
            defense: $battleStats['defense'],
            agility: $battleStats['agility'],
            evasion: $battleStats['evasion'],
            accuracy: $battleStats['accuracy'],
            magic_attack: $battleStats['magic_attack'],
            equipment_effects: $battleStats['equipment_effects'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'hp' => $this->hp,
            'max_hp' => $this->max_hp,
            'sp' => $this->sp,
            'max_sp' => $this->max_sp,
            'mp' => $this->mp,
            'max_mp' => $this->max_mp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'agility' => $this->agility,
            'evasion' => $this->evasion,
            'accuracy' => $this->accuracy,
            'magic_attack' => $this->magic_attack,
            'equipment_effects' => $this->equipment_effects,
        ];
    }

    public function getHpPercentage(): float
    {
        return $this->max_hp > 0 ? ($this->hp / $this->max_hp) * 100 : 0;
    }

    public function getSpPercentage(): float
    {
        return $this->max_sp > 0 ? ($this->sp / $this->max_sp) * 100 : 0;
    }
}

/**
 * モンスター戦闘統計DTO
 */
class MonsterBattleStats
{
    public function __construct(
        public readonly int $level,
        public readonly int $hp,
        public readonly int $max_hp,
        public readonly int $attack,
        public readonly int $defense,
        public readonly int $agility,
        public readonly int $evasion,
        public readonly int $accuracy,
        public readonly array $special_abilities = []
    ) {}

    public static function fromMonster(Monster $monster): self
    {
        return new self(
            level: $monster->level ?? 1,
            hp: $monster->hp ?? 100,
            max_hp: $monster->max_hp ?? 100,
            attack: $monster->attack ?? 15,
            defense: $monster->defense ?? 10,
            agility: $monster->agility ?? 12,
            evasion: $monster->evasion ?? 15,
            accuracy: $monster->accuracy ?? 85,
            special_abilities: $monster->special_abilities ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'hp' => $this->hp,
            'max_hp' => $this->max_hp,
            'attack' => $this->attack,
            'defense' => $this->defense,
            'agility' => $this->agility,
            'evasion' => $this->evasion,
            'accuracy' => $this->accuracy,
            'special_abilities' => $this->special_abilities,
        ];
    }

    public function getHpPercentage(): float
    {
        return $this->max_hp > 0 ? ($this->hp / $this->max_hp) * 100 : 0;
    }
}

/**
 * 戦闘結果DTO
 */
class BattleResult
{
    public function __construct(
        public readonly bool $victory,
        public readonly bool $character_alive,
        public readonly bool $monster_defeated,
        public readonly int $experience_gained,
        public readonly int $gold_gained,
        public readonly array $items_gained = [],
        public readonly array $battle_log = [],
        public readonly ?string $message = null
    ) {}

    public static function victory(
        int $experienceGained,
        int $goldGained,
        array $itemsGained = [],
        array $battleLog = []
    ): self {
        return new self(
            victory: true,
            character_alive: true,
            monster_defeated: true,
            experience_gained: $experienceGained,
            gold_gained: $goldGained,
            items_gained: $itemsGained,
            battle_log: $battleLog,
            message: '戦闘に勝利しました！'
        );
    }

    public static function defeat(array $battleLog = []): self
    {
        return new self(
            victory: false,
            character_alive: false,
            monster_defeated: false,
            experience_gained: 0,
            gold_gained: 0,
            battle_log: $battleLog,
            message: '戦闘に敗北しました...'
        );
    }

    public function toArray(): array
    {
        return [
            'victory' => $this->victory,
            'character_alive' => $this->character_alive,
            'monster_defeated' => $this->monster_defeated,
            'experience_gained' => $this->experience_gained,
            'gold_gained' => $this->gold_gained,
            'items_gained' => $this->items_gained,
            'battle_log' => $this->battle_log,
            'message' => $this->message,
        ];
    }
}

/**
 * 戦闘状態列挙型
 */
enum BattleState: string
{
    case NONE = 'none';
    case READY = 'ready';
    case STARTING = 'starting';
    case ACTIVE = 'active';
    case VICTORY = 'victory';
    case DEFEAT = 'defeat';
    case ESCAPED = 'escaped';
}