# 型定義・データ構造設計書

## 文書の概要

- **作成日**: 2025年7月25日
- **対象システム**: test_smg（Laravel/PHPブラウザRPG）
- **作成者**: AI開発チーム
- **バージョン**: v1.0

## 目的

test_smgシステムにおける型定義とデータ構造の詳細な設計を定義し、開発チーム全体での統一された実装を実現する。

## 目次

1. [基本設計方針](#基本設計方針)
2. [PHP型定義](#php型定義)
3. [JavaScript型定義](#javascript型定義)
4. [DTO設計](#dto設計)
5. [JSON構造設計](#json構造設計)
6. [データベース型定義](#データベース型定義)
7. [型変換・バリデーション](#型変換バリデーション)
8. [拡張性設計](#拡張性設計)
9. [テスト戦略](#テスト戦略)

## 基本設計方針

### 1. 型安全性の確保
```php
// 厳密な型宣言の使用
declare(strict_types=1);

// PHPDocによる詳細な型定義
/** @var array<int, GameItem> $inventory */
/** @return Collection<int, Character> */
```

### 2. DTO（Data Transfer Object）パターン
- レイヤー間のデータ交換における型安全性確保
- 外部APIとの通信データ構造の明確化
- バリデーションロジックの集約

### 3. JSON構造の統一
- APIレスポンス形式の標準化
- フロントエンド・バックエンド間のデータ契約明確化
- ゲーム状態データの構造化

## PHP型定義

### 基本型・スカラー型
```php
<?php

declare(strict_types=1);

namespace App\Types;

/**
 * 基本的なゲーム数値型
 */
class GameTypes
{
    /** @var int 最小HP値 */
    public const MIN_HP = 1;
    
    /** @var int 最大HP値 */
    public const MAX_HP = 9999;
    
    /** @var int 最小レベル */
    public const MIN_LEVEL = 1;
    
    /** @var int 最大レベル */
    public const MAX_LEVEL = 100;
    
    /** @var int 最大スキルレベル */
    public const MAX_SKILL_LEVEL = 100;
    
    /** @var int 最大移動距離 */
    public const MAX_MOVEMENT = 30;
}

/**
 * ID型の厳密な定義
 */
final readonly class CharacterId
{
    public function __construct(
        public int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Character ID must be positive');
        }
    }
    
    public function equals(CharacterId $other): bool
    {
        return $this->value === $other->value;
    }
}

final readonly class LocationId
{
    public function __construct(
        public int $value
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('Location ID must be positive');
        }
    }
}
```

### Enum型定義
```php
<?php

namespace App\Enums;

/**
 * 場所の種類
 */
enum LocationType: string
{
    case TOWN = 'town';
    case ROAD = 'road';
    case DUNGEON = 'dungeon';
    case SHOP = 'shop';
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::TOWN => '町',
            self::ROAD => '道路',
            self::DUNGEON => 'ダンジョン',
            self::SHOP => 'ショップ',
        };
    }
    
    public function canMove(): bool
    {
        return match($this) {
            self::ROAD, self::DUNGEON => true,
            self::TOWN, self::SHOP => false,
        };
    }
}

/**
 * 戦闘結果
 */
enum BattleResult: string
{
    case VICTORY = 'victory';
    case DEFEAT = 'defeat';
    case ESCAPE = 'escape';
    case ONGOING = 'ongoing';
}

/**
 * スキルタイプ
 */
enum SkillType: string
{
    case ATTACK = 'attack';
    case DEFENSE = 'defense';
    case AGILITY = 'agility';
    case GATHERING = 'gathering';
    case CRAFTING = 'crafting';
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::ATTACK => '攻撃',
            self::DEFENSE => '防御',
            self::AGILITY => '敏捷',
            self::GATHERING => '採集',
            self::CRAFTING => '製作',
        };
    }
}

/**
 * アイテム品質
 */
enum ItemQuality: int
{
    case NORMAL = 1;
    case GOOD = 2;
    case EXCELLENT = 3;
    case RARE = 4;
    case LEGENDARY = 5;
    
    public function getColorCode(): string
    {
        return match($this) {
            self::NORMAL => '#ffffff',
            self::GOOD => '#1eff00',
            self::EXCELLENT => '#0099ff',
            self::RARE => '#cc00ff',
            self::LEGENDARY => '#ff8000',
        };
    }
}
```

### 複合型・配列型
```php
<?php

namespace App\Types;

/**
 * スキルセット型
 * @template-implements ArrayAccess<SkillType, int>
 */
final class SkillSet implements ArrayAccess, JsonSerializable
{
    /** @var array<string, int> */
    private array $skills = [];
    
    public function __construct(array $skills = [])
    {
        foreach ($skills as $type => $level) {
            $this->setSkill(SkillType::from($type), $level);
        }
    }
    
    public function setSkill(SkillType $type, int $level): void
    {
        if ($level < 0 || $level > GameTypes::MAX_SKILL_LEVEL) {
            throw new InvalidArgumentException('Invalid skill level');
        }
        $this->skills[$type->value] = $level;
    }
    
    public function getSkill(SkillType $type): int
    {
        return $this->skills[$type->value] ?? 0;
    }
    
    public function getTotalLevel(): int
    {
        return array_sum($this->skills);
    }
    
    public function getCharacterLevel(): int
    {
        return intval($this->getTotalLevel() / 10) + 1;
    }
    
    // ArrayAccess実装
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->skills[$offset]);
    }
    
    public function offsetGet(mixed $offset): int
    {
        return $this->getSkill(SkillType::from($offset));
    }
    
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setSkill(SkillType::from($offset), $value);
    }
    
    public function offsetUnset(mixed $offset): void
    {
        unset($this->skills[$offset]);
    }
    
    public function jsonSerialize(): array
    {
        return $this->skills;
    }
}

/**
 * ゲーム座標
 */
final readonly class GamePosition
{
    public function __construct(
        public int $x,
        public int $y,
        public int $position = 0
    ) {
        if ($position < 0 || $position > 100) {
            throw new InvalidArgumentException('Position must be between 0 and 100');
        }
    }
    
    public function isAtBoundary(): bool
    {
        return $this->position === 0 || $this->position === 100;
    }
    
    public function move(int $steps): self
    {
        $newPosition = max(0, min(100, $this->position + $steps));
        return new self($this->x, $this->y, $newPosition);
    }
}

/**
 * インベントリスロット
 */
final class InventorySlot implements JsonSerializable
{
    public function __construct(
        public readonly int $slot,
        public readonly ?int $itemId = null,
        public readonly int $quantity = 0,
        public readonly ?ItemQuality $quality = null
    ) {
        if ($slot < 0 || $slot >= 30) {
            throw new InvalidArgumentException('Invalid slot number');
        }
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative');
        }
    }
    
    public function isEmpty(): bool
    {
        return $this->itemId === null || $this->quantity === 0;
    }
    
    public function isFull(int $maxStack = 99): bool
    {
        return $this->quantity >= $maxStack;
    }
    
    public function jsonSerialize(): array
    {
        return [
            'slot' => $this->slot,
            'item_id' => $this->itemId,
            'quantity' => $this->quantity,
            'quality' => $this->quality?->value,
        ];
    }
}
```

## JavaScript型定義

### TypeScript型定義（参考）
```typescript
// types/game.ts

/**
 * 基本的なゲーム型定義
 */
export type GameId = number & { readonly brand: unique symbol };
export type CharacterId = GameId;
export type LocationId = GameId;
export type ItemId = GameId;

export enum LocationType {
    TOWN = 'town',
    ROAD = 'road',
    DUNGEON = 'dungeon',
    SHOP = 'shop'
}

export enum SkillType {
    ATTACK = 'attack',
    DEFENSE = 'defense',
    AGILITY = 'agility',
    GATHERING = 'gathering',
    CRAFTING = 'crafting'
}

export interface GamePosition {
    readonly x: number;
    readonly y: number;
    readonly position: number;
}

export interface SkillSet {
    readonly [key in SkillType]: number;
}

export interface InventorySlot {
    readonly slot: number;
    readonly itemId: ItemId | null;
    readonly quantity: number;
    readonly quality: number | null;
}

export interface Character {
    readonly id: CharacterId;
    readonly name: string;
    readonly hp: number;
    readonly maxHp: number;
    readonly sp: number;
    readonly maxSp: number;
    readonly gamePosition: GamePosition;
    readonly locationType: LocationType;
    readonly skills: SkillSet;
    readonly inventory: ReadonlyArray<InventorySlot>;
}

export interface GameState {
    readonly character: Character;
    readonly currentLocation: Location;
    readonly nextLocation: Location | null;
    readonly canMoveToNext: boolean;
}

/**
 * API応答型
 */
export interface ApiResponse<T = unknown> {
    readonly success: boolean;
    readonly data?: T;
    readonly message?: string;
    readonly errors?: ReadonlyArray<string>;
}

export interface DiceResult {
    readonly diceRolls: ReadonlyArray<number>;
    readonly baseTotal: number;
    readonly bonus: number;
    readonly finalMovement: number;
}

export interface MoveResult {
    readonly position: number;
    readonly currentLocation: Location;
    readonly nextLocation: Location | null;
    readonly canMoveToNext: boolean;
    readonly encounter: boolean;
    readonly monster?: Monster;
}
```

### JavaScript実装（ES6+）
```javascript
// js/types/gameTypes.js

/**
 * JavaScript実装での型チェック関数
 */
class GameTypes {
    /**
     * @param {unknown} value
     * @returns {boolean}
     */
    static isCharacterId(value) {
        return Number.isInteger(value) && value > 0;
    }
    
    /**
     * @param {unknown} value
     * @returns {boolean}
     */
    static isLocationId(value) {
        return Number.isInteger(value) && value > 0;
    }
    
    /**
     * @param {unknown} value
     * @returns {boolean}
     */
    static isValidPosition(value) {
        return Number.isInteger(value) && value >= 0 && value <= 100;
    }
    
    /**
     * @param {unknown} value
     * @returns {boolean}
     */
    static isSkillLevel(value) {
        return Number.isInteger(value) && value >= 0 && value <= 100;
    }
}

/**
 * ゲーム座標クラス
 */
class GamePosition {
    /**
     * @param {number} x
     * @param {number} y
     * @param {number} position
     */
    constructor(x, y, position = 0) {
        if (!GameTypes.isValidPosition(position)) {
            throw new Error('Invalid position value');
        }
        
        this.x = x;
        this.y = y;
        this.position = position;
        Object.freeze(this);
    }
    
    /**
     * @returns {boolean}
     */
    isAtBoundary() {
        return this.position === 0 || this.position === 100;
    }
    
    /**
     * @param {number} steps
     * @returns {GamePosition}
     */
    move(steps) {
        const newPosition = Math.max(0, Math.min(100, this.position + steps));
        return new GamePosition(this.x, this.y, newPosition);
    }
    
    /**
     * @returns {Object}
     */
    toJSON() {
        return {
            x: this.x,
            y: this.y,
            position: this.position
        };
    }
}

/**
 * スキルセットクラス
 */
class SkillSet {
    /**
     * @param {Object<string, number>} skills
     */
    constructor(skills = {}) {
        this.skills = new Map();
        
        for (const [type, level] of Object.entries(skills)) {
            this.setSkill(type, level);
        }
        
        Object.freeze(this);
    }
    
    /**
     * @param {string} type
     * @param {number} level
     */
    setSkill(type, level) {
        if (!GameTypes.isSkillLevel(level)) {
            throw new Error('Invalid skill level');
        }
        this.skills.set(type, level);
    }
    
    /**
     * @param {string} type
     * @returns {number}
     */
    getSkill(type) {
        return this.skills.get(type) ?? 0;
    }
    
    /**
     * @returns {number}
     */
    getTotalLevel() {
        return Array.from(this.skills.values()).reduce((sum, level) => sum + level, 0);
    }
    
    /**
     * @returns {number}
     */
    getCharacterLevel() {
        return Math.floor(this.getTotalLevel() / 10) + 1;
    }
    
    /**
     * @returns {Object}
     */
    toJSON() {
        return Object.fromEntries(this.skills);
    }
}
```

## DTO設計

### レスポンスDTO
```php
<?php

namespace App\Application\DTOs;

/**
 * ゲーム状態DTO
 */
final readonly class GameStateDto
{
    public function __construct(
        public CharacterDto $character,
        public LocationDto $currentLocation,
        public ?LocationDto $nextLocation = null,
        public bool $canMoveToNext = false,
        public ?array $additional = null
    ) {}
    
    public function toArray(): array
    {
        return [
            'character' => $this->character->toArray(),
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'canMoveToNext' => $this->canMoveToNext,
            'additional' => $this->additional,
        ];
    }
}

/**
 * キャラクターDTO
 */
final readonly class CharacterDto
{
    public function __construct(
        public int $id,
        public string $name,
        public int $hp,
        public int $maxHp,
        public int $sp,
        public int $maxSp,
        public int $gamePosition,
        public LocationType $locationType,
        public SkillSet $skills,
        public array $inventory = []
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hp' => $this->hp,
            'max_hp' => $this->maxHp,
            'sp' => $this->sp,
            'max_sp' => $this->maxSp,
            'game_position' => $this->gamePosition,
            'location_type' => $this->locationType->value,
            'skills' => $this->skills->jsonSerialize(),
            'inventory' => $this->inventory,
        ];
    }
}

/**
 * 場所DTO
 */
final readonly class LocationDto
{
    public function __construct(
        public int $id,
        public string $name,
        public LocationType $type,
        public ?string $description = null,
        public ?array $facilities = null
    ) {}
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'description' => $this->description,
            'facilities' => $this->facilities,
        ];
    }
}

/**
 * サイコロ結果DTO
 */
final readonly class DiceResultDto
{
    public function __construct(
        public array $diceRolls,
        public int $baseTotal,
        public int $bonus,
        public int $finalMovement
    ) {}
    
    public function toArray(): array
    {
        return [
            'dice_rolls' => $this->diceRolls,
            'base_total' => $this->baseTotal,
            'bonus' => $this->bonus,
            'final_movement' => $this->finalMovement,
        ];
    }
}

/**
 * 移動結果DTO
 */
final readonly class MoveResultDto
{
    public function __construct(
        public int $position,
        public LocationDto $currentLocation,
        public ?LocationDto $nextLocation = null,
        public bool $canMoveToNext = false,
        public bool $encounter = false,
        public ?MonsterDto $monster = null
    ) {}
    
    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'canMoveToNext' => $this->canMoveToNext,
            'encounter' => $this->encounter,
            'monster' => $this->monster?->toArray(),
        ];
    }
}

/**
 * 戦闘結果DTO
 */
final readonly class BattleResultDto
{
    public function __construct(
        public BattleResult $result,
        public int $damageDealt,
        public int $damageTaken,
        public int $experienceGained = 0,
        public ?array $rewards = null,
        public bool $levelUp = false,
        public ?SkillSet $newSkills = null
    ) {}
    
    public function toArray(): array
    {
        return [
            'result' => $this->result->value,
            'damage_dealt' => $this->damageDealt,
            'damage_taken' => $this->damageTaken,
            'experience_gained' => $this->experienceGained,
            'rewards' => $this->rewards,
            'level_up' => $this->levelUp,
            'new_skills' => $this->newSkills?->jsonSerialize(),
        ];
    }
}
```

### リクエストDTO
```php
<?php

namespace App\Application\DTOs;

/**
 * 移動リクエストDTO
 */
final readonly class MoveRequestDto
{
    public function __construct(
        public string $direction,
        public int $steps
    ) {
        if (!in_array($direction, ['left', 'right', 'forward', 'backward'])) {
            throw new InvalidArgumentException('Invalid direction');
        }
        
        if ($steps < 1 || $steps > GameTypes::MAX_MOVEMENT) {
            throw new InvalidArgumentException('Invalid steps');
        }
    }
    
    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->validated('direction'),
            $request->validated('steps')
        );
    }
}

/**
 * 戦闘行動リクエストDTO
 */
final readonly class BattleActionDto
{
    public function __construct(
        public string $action,
        public ?SkillType $skillType = null,
        public ?ItemId $itemId = null
    ) {
        if (!in_array($action, ['attack', 'defend', 'escape', 'use_skill', 'use_item'])) {
            throw new InvalidArgumentException('Invalid battle action');
        }
    }
    
    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->validated('action'),
            $request->has('skill_type') ? SkillType::from($request->validated('skill_type')) : null,
            $request->has('item_id') ? new ItemId($request->validated('item_id')) : null
        );
    }
}
```

## JSON構造設計

### API共通レスポンス形式
```json
{
    "success": true,
    "data": {
        // 実際のデータ
    },
    "message": "操作が完了しました",
    "timestamp": "2025-07-25T10:00:00Z",
    "version": "1.0"
}

// エラー時
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "入力データが無効です",
        "details": [
            "direction: 必須項目です",
            "steps: 1以上30以下である必要があります"
        ]
    },
    "timestamp": "2025-07-25T10:00:00Z",
    "version": "1.0"
}
```

### ゲーム状態JSON
```json
{
    "character": {
        "id": 1,
        "name": "プレイヤー1",
        "hp": 100,
        "max_hp": 100,
        "sp": 50,
        "max_sp": 50,
        "game_position": 45,
        "location_type": "road",
        "skills": {
            "attack": 15,
            "defense": 12,
            "agility": 18,
            "gathering": 8,
            "crafting": 5
        },
        "inventory": [
            {
                "slot": 0,
                "item_id": 1,
                "quantity": 3,
                "quality": 2
            },
            {
                "slot": 1,
                "item_id": null,
                "quantity": 0,
                "quality": null
            }
        ]
    },
    "currentLocation": {
        "id": 2,
        "name": "森の道",
        "type": "road",
        "description": "緑豊かな森を通る道です",
        "facilities": null
    },
    "nextLocation": {
        "id": 3,
        "name": "山の町",
        "type": "town",
        "description": "山の麓にある小さな町です",
        "facilities": ["item_shop", "blacksmith"]
    },
    "canMoveToNext": true
}
```

### 戦闘データJSON
```json
{
    "battle": {
        "id": "battle_123",
        "turn": 3,
        "status": "ongoing"
    },
    "character": {
        "hp": 75,
        "max_hp": 100,
        "sp": 30,
        "max_sp": 50,
        "status_effects": ["poison", "blessed"]
    },
    "monster": {
        "id": 5,
        "name": "森のオーク",
        "emoji": "👹",
        "hp": 45,
        "max_hp": 80,
        "level": 8
    },
    "lastAction": {
        "actor": "character",
        "action": "attack",
        "damage": 25,
        "critical": false,
        "message": "オークに25ダメージを与えた！"
    }
}
```

## データベース型定義

### Eloquentモデル型定義
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Character Model
 * 
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int $hp
 * @property int $max_hp
 * @property int $sp
 * @property int $max_sp
 * @property int $game_position
 * @property LocationType $location_type
 * @property array<string, int> $skills
 * @property array<int, array> $inventory
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * 
 * @property-read User $user
 * @property-read Collection<int, BattleLog> $battleLogs
 */
class Character extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'hp',
        'max_hp',
        'sp',
        'max_sp',
        'game_position',
        'location_type',
        'skills',
        'inventory',
    ];
    
    protected $casts = [
        'hp' => 'integer',
        'max_hp' => 'integer',
        'sp' => 'integer',
        'max_sp' => 'integer',
        'game_position' => 'integer',
        'location_type' => LocationType::class,
        'skills' => 'array',
        'inventory' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function battleLogs(): HasMany
    {
        return $this->hasMany(BattleLog::class);
    }
    
    /**
     * スキルセットを取得
     */
    public function getSkillSet(): SkillSet
    {
        return new SkillSet($this->skills ?? []);
    }
    
    /**
     * キャラクターレベルを取得
     */
    public function getLevel(): int
    {
        return $this->getSkillSet()->getCharacterLevel();
    }
    
    /**
     * 型安全なインベントリアクセス
     * @return array<int, InventorySlot>
     */
    public function getInventorySlots(): array
    {
        $slots = [];
        $inventory = $this->inventory ?? [];
        
        for ($i = 0; $i < 30; $i++) {
            $slotData = $inventory[$i] ?? [];
            $slots[$i] = new InventorySlot(
                $i,
                $slotData['item_id'] ?? null,
                $slotData['quantity'] ?? 0,
                isset($slotData['quality']) ? ItemQuality::from($slotData['quality']) : null
            );
        }
        
        return $slots;
    }
}

/**
 * Location Model
 * 
 * @property int $id
 * @property string $name
 * @property LocationType $type
 * @property string|null $description
 * @property int $x
 * @property int $y
 * @property array|null $facilities
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Location extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'x',
        'y',
        'facilities',
    ];
    
    protected $casts = [
        'type' => LocationType::class,
        'x' => 'integer',
        'y' => 'integer',
        'facilities' => 'array',
    ];
    
    /**
     * 移動可能かチェック
     */
    public function canMove(): bool
    {
        return $this->type->canMove();
    }
}
```

### マイグレーション型制約
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 50);
            $table->unsignedSmallInteger('hp')->default(100);
            $table->unsignedSmallInteger('max_hp')->default(100);
            $table->unsignedSmallInteger('sp')->default(50);
            $table->unsignedSmallInteger('max_sp')->default(50);
            $table->unsignedTinyInteger('game_position')->default(0);
            $table->enum('location_type', ['town', 'road', 'dungeon', 'shop'])->default('town');
            $table->json('skills')->nullable();
            $table->json('inventory')->nullable();
            $table->timestamps();
            
            // 制約の追加
            $table->check('hp >= 0');
            $table->check('max_hp > 0');
            $table->check('sp >= 0');
            $table->check('max_sp > 0');
            $table->check('game_position >= 0 AND game_position <= 100');
            $table->check('hp <= max_hp');
            $table->check('sp <= max_sp');
            
            // インデックス
            $table->index(['user_id', 'location_type']);
            $table->index('game_position');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
```

## 型変換・バリデーション

### リクエストバリデーション
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidDirection;
use App\Rules\ValidSkillType;

class MoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'direction' => ['required', 'string', new ValidDirection()],
            'steps' => ['required', 'integer', 'min:1', 'max:30'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'direction.required' => '移動方向を指定してください',
            'steps.required' => '移動歩数を指定してください',
            'steps.min' => '移動歩数は1以上である必要があります',
            'steps.max' => '移動歩数は30以下である必要があります',
        ];
    }
    
    /**
     * 型安全なDTOに変換
     */
    public function toDto(): MoveRequestDto
    {
        return MoveRequestDto::fromRequest($this);
    }
}

class BattleActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:attack,defend,escape,use_skill,use_item'],
            'skill_type' => ['required_if:action,use_skill', new ValidSkillType()],
            'item_id' => ['required_if:action,use_item', 'integer', 'exists:items,id'],
        ];
    }
    
    public function toDto(): BattleActionDto
    {
        return BattleActionDto::fromRequest($this);
    }
}
```

### カスタムバリデーションルール
```php
<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidDirection implements Rule
{
    private const VALID_DIRECTIONS = ['left', 'right', 'forward', 'backward'];
    
    public function passes($attribute, $value): bool
    {
        return is_string($value) && in_array($value, self::VALID_DIRECTIONS);
    }
    
    public function message(): string
    {
        return '移動方向は left, right, forward, backward のいずれかである必要があります';
    }
}

class ValidSkillType implements Rule
{
    public function passes($attribute, $value): bool
    {
        try {
            SkillType::from($value);
            return true;
        } catch (ValueError) {
            return false;
        }
    }
    
    public function message(): string
    {
        $validTypes = implode(', ', array_column(SkillType::cases(), 'value'));
        return "スキルタイプは次のいずれかである必要があります: {$validTypes}";
    }
}

class ValidInventorySlot implements Rule
{
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        $required = ['slot', 'item_id', 'quantity'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $value)) {
                return false;
            }
        }
        
        if (!is_int($value['slot']) || $value['slot'] < 0 || $value['slot'] >= 30) {
            return false;
        }
        
        if ($value['item_id'] !== null && (!is_int($value['item_id']) || $value['item_id'] <= 0)) {
            return false;
        }
        
        if (!is_int($value['quantity']) || $value['quantity'] < 0) {
            return false;
        }
        
        return true;
    }
    
    public function message(): string
    {
        return 'インベントリスロットの形式が正しくありません';
    }
}
```

### 型変換ヘルパー
```php
<?php

namespace App\Helpers;

class TypeConverter
{
    /**
     * 安全な整数変換
     */
    public static function toInt($value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }
        
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        
        return $default;
    }
    
    /**
     * 安全な配列変換
     */
    public static function toArray($value, array $default = []): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $default;
        }
        
        return $default;
    }
    
    /**
     * LocationTypeの安全な変換
     */
    public static function toLocationType($value, LocationType $default = LocationType::TOWN): LocationType
    {
        if ($value instanceof LocationType) {
            return $value;
        }
        
        if (is_string($value)) {
            try {
                return LocationType::from($value);
            } catch (ValueError) {
                return $default;
            }
        }
        
        return $default;
    }
    
    /**
     * SkillSetの安全な変換
     */
    public static function toSkillSet($value): SkillSet
    {
        if ($value instanceof SkillSet) {
            return $value;
        }
        
        $skills = self::toArray($value);
        return new SkillSet($skills);
    }
}
```

## 拡張性設計

### 型の拡張ポイント
```php
<?php

namespace App\Types\Extensions;

/**
 * 新しいスキルタイプの追加に対応
 */
interface SkillTypeExtensible
{
    public function addCustomSkillType(string $name, string $displayName): void;
    public function getCustomSkillTypes(): array;
}

/**
 * 新しいLocationTypeの追加に対応
 */
interface LocationTypeExtensible
{
    public function addCustomLocationType(string $type, string $displayName, bool $canMove): void;
    public function getCustomLocationTypes(): array;
}

/**
 * カスタムアイテム品質の追加
 */
interface ItemQualityExtensible
{
    public function addCustomQuality(int $level, string $name, string $colorCode): void;
    public function getCustomQualities(): array;
}

/**
 * 拡張可能なゲーム設定
 */
class ExtensibleGameConfig
{
    private static array $customSkillTypes = [];
    private static array $customLocationTypes = [];
    private static array $customItemQualities = [];
    
    public static function addSkillType(string $key, string $displayName): void
    {
        self::$customSkillTypes[$key] = $displayName;
    }
    
    public static function getSkillTypes(): array
    {
        $defaults = array_column(SkillType::cases(), 'value');
        return array_merge($defaults, array_keys(self::$customSkillTypes));
    }
    
    public static function addLocationType(string $key, string $displayName, bool $canMove = false): void
    {
        self::$customLocationTypes[$key] = [
            'display_name' => $displayName,
            'can_move' => $canMove,
        ];
    }
    
    public static function getLocationTypes(): array
    {
        $defaults = array_column(LocationType::cases(), 'value');
        return array_merge($defaults, array_keys(self::$customLocationTypes));
    }
}
```

### バージョン管理
```php
<?php

namespace App\Types\Versioning;

/**
 * APIバージョン管理
 */
class ApiVersionManager
{
    private const CURRENT_VERSION = '1.0';
    private const SUPPORTED_VERSIONS = ['1.0'];
    
    public static function getCurrentVersion(): string
    {
        return self::CURRENT_VERSION;
    }
    
    public static function isSupported(string $version): bool
    {
        return in_array($version, self::SUPPORTED_VERSIONS);
    }
    
    /**
     * バージョン間の型変換
     */
    public static function convertBetweenVersions(array $data, string $from, string $to): array
    {
        if ($from === $to) {
            return $data;
        }
        
        // バージョン変換ロジック
        return match ([$from, $to]) {
            ['1.0', '1.1'] => self::convertFrom10To11($data),
            ['1.1', '1.0'] => self::convertFrom11To10($data),
            default => throw new UnsupportedVersionException("Conversion from {$from} to {$to} not supported"),
        };
    }
    
    private static function convertFrom10To11(array $data): array
    {
        // 1.0 -> 1.1 の変換ロジック
        return $data;
    }
    
    private static function convertFrom11To10(array $data): array
    {
        // 1.1 -> 1.0 の変換ロジック
        return $data;
    }
}

/**
 * 型定義のマイグレーション
 */
class TypeMigrationManager
{
    /**
     * 古い形式のデータを新しい形式に変換
     */
    public static function migrateCharacterData(array $oldData): array
    {
        // 古いスキル形式から新しい形式への変換
        if (isset($oldData['attack_skill'], $oldData['defense_skill'])) {
            $oldData['skills'] = [
                'attack' => $oldData['attack_skill'],
                'defense' => $oldData['defense_skill'],
                'agility' => $oldData['agility_skill'] ?? 0,
                'gathering' => $oldData['gathering_skill'] ?? 0,
                'crafting' => $oldData['crafting_skill'] ?? 0,
            ];
            
            unset($oldData['attack_skill'], $oldData['defense_skill'], 
                  $oldData['agility_skill'], $oldData['gathering_skill'], 
                  $oldData['crafting_skill']);
        }
        
        // 古いインベントリ形式から新しい形式への変換
        if (isset($oldData['items']) && !isset($oldData['inventory'])) {
            $inventory = [];
            foreach ($oldData['items'] as $index => $item) {
                $inventory[] = [
                    'slot' => $index,
                    'item_id' => $item['id'] ?? null,
                    'quantity' => $item['count'] ?? 0,
                    'quality' => $item['quality'] ?? 1,
                ];
            }
            $oldData['inventory'] = $inventory;
            unset($oldData['items']);
        }
        
        return $oldData;
    }
}
```

## テスト戦略

### 型テストの実装
```php
<?php

namespace Tests\Unit\Types;

use PHPUnit\Framework\TestCase;
use App\Types\SkillSet;
use App\Enums\SkillType;
use App\Types\GamePosition;
use App\Types\InventorySlot;
use App\Enums\ItemQuality;

class SkillSetTest extends TestCase
{
    public function test_can_create_skill_set_with_valid_data(): void
    {
        $skills = [
            'attack' => 15,
            'defense' => 12,
            'agility' => 18,
        ];
        
        $skillSet = new SkillSet($skills);
        
        $this->assertEquals(15, $skillSet->getSkill(SkillType::ATTACK));
        $this->assertEquals(12, $skillSet->getSkill(SkillType::DEFENSE));
        $this->assertEquals(18, $skillSet->getSkill(SkillType::AGILITY));
        $this->assertEquals(0, $skillSet->getSkill(SkillType::GATHERING));
    }
    
    public function test_calculates_total_level_correctly(): void
    {
        $skills = [
            'attack' => 15,
            'defense' => 12,
            'agility' => 18,
        ];
        
        $skillSet = new SkillSet($skills);
        
        $this->assertEquals(45, $skillSet->getTotalLevel());
        $this->assertEquals(5, $skillSet->getCharacterLevel()); // 45/10 + 1 = 5
    }
    
    public function test_throws_exception_for_invalid_skill_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new SkillSet(['attack' => 101]); // 最大レベル100を超える
    }
    
    public function test_skill_set_is_json_serializable(): void
    {
        $skills = ['attack' => 15, 'defense' => 12];
        $skillSet = new SkillSet($skills);
        
        $json = json_encode($skillSet);
        $decoded = json_decode($json, true);
        
        $this->assertEquals($skills, $decoded);
    }
}

class GamePositionTest extends TestCase
{
    public function test_can_create_valid_position(): void
    {
        $position = new GamePosition(10, 20, 50);
        
        $this->assertEquals(10, $position->x);
        $this->assertEquals(20, $position->y);
        $this->assertEquals(50, $position->position);
    }
    
    public function test_throws_exception_for_invalid_position(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new GamePosition(10, 20, 150); // 100を超える
    }
    
    public function test_detects_boundary_positions(): void
    {
        $atStart = new GamePosition(0, 0, 0);
        $atEnd = new GamePosition(0, 0, 100);
        $inMiddle = new GamePosition(0, 0, 50);
        
        $this->assertTrue($atStart->isAtBoundary());
        $this->assertTrue($atEnd->isAtBoundary());
        $this->assertFalse($inMiddle->isAtBoundary());
    }
    
    public function test_move_respects_boundaries(): void
    {
        $position = new GamePosition(0, 0, 95);
        
        $moved = $position->move(10);
        $this->assertEquals(100, $moved->position);
        
        $moved = $position->move(-100);
        $this->assertEquals(0, $moved->position);
    }
}

class InventorySlotTest extends TestCase
{
    public function test_can_create_empty_slot(): void
    {
        $slot = new InventorySlot(0);
        
        $this->assertTrue($slot->isEmpty());
        $this->assertEquals(0, $slot->slot);
        $this->assertNull($slot->itemId);
        $this->assertEquals(0, $slot->quantity);
    }
    
    public function test_can_create_filled_slot(): void
    {
        $slot = new InventorySlot(5, 123, 10, ItemQuality::RARE);
        
        $this->assertFalse($slot->isEmpty());
        $this->assertEquals(5, $slot->slot);
        $this->assertEquals(123, $slot->itemId);
        $this->assertEquals(10, $slot->quantity);
        $this->assertEquals(ItemQuality::RARE, $slot->quality);
    }
    
    public function test_throws_exception_for_invalid_slot_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new InventorySlot(30); // 最大29まで
    }
    
    public function test_slot_is_json_serializable(): void
    {
        $slot = new InventorySlot(5, 123, 10, ItemQuality::RARE);
        
        $json = json_encode($slot);
        $decoded = json_decode($json, true);
        
        $expected = [
            'slot' => 5,
            'item_id' => 123,
            'quantity' => 10,
            'quality' => ItemQuality::RARE->value,
        ];
        
        $this->assertEquals($expected, $decoded);
    }
}
```

### 統合テスト
```php
<?php

namespace Tests\Feature\Types;

use Tests\TestCase;
use App\Models\Character;
use App\Models\User;
use App\Application\DTOs\CharacterDto;
use App\Enums\LocationType;
use App\Types\SkillSet;

class CharacterDtoTest extends TestCase
{
    public function test_can_create_dto_from_model(): void
    {
        $user = User::factory()->create();
        $character = Character::factory()->create([
            'user_id' => $user->id,
            'name' => 'テストキャラクター',
            'hp' => 80,
            'max_hp' => 100,
            'sp' => 40,
            'max_sp' => 50,
            'game_position' => 25,
            'location_type' => LocationType::ROAD,
            'skills' => ['attack' => 15, 'defense' => 10],
        ]);
        
        $skillSet = new SkillSet(['attack' => 15, 'defense' => 10]);
        
        $dto = new CharacterDto(
            $character->id,
            $character->name,
            $character->hp,
            $character->max_hp,
            $character->sp,
            $character->max_sp,
            $character->game_position,
            $character->location_type,
            $skillSet
        );
        
        $array = $dto->toArray();
        
        $this->assertEquals($character->id, $array['id']);
        $this->assertEquals('テストキャラクター', $array['name']);
        $this->assertEquals(80, $array['hp']);
        $this->assertEquals('road', $array['location_type']);
        $this->assertEquals(['attack' => 15, 'defense' => 10], $array['skills']);
    }
}
```

## まとめ

### 設計原則の遵守
1. **型安全性**: PHPの型システムを最大限活用し、実行時エラーを防止
2. **可読性**: 明確な型定義により、コードの意図を明確化
3. **保守性**: DTOパターンにより、データ構造の変更影響を局所化
4. **拡張性**: Enumやinterfaceを活用し、将来の機能追加に対応

### 実装のベストプラクティス
1. **strict_types宣言**: 全PHPファイルで型の厳密性を確保
2. **readonly修飾子**: 不変性を保証し、サイドエフェクトを防止
3. **バリデーション**: 型レベルとビジネスロジックレベルの二段階検証
4. **テスト**: 型に関する境界値や例外ケースの網羅的テスト

この型定義・データ構造設計により、test_smgシステムの堅牢性と保守性を大幅に向上させることができます。