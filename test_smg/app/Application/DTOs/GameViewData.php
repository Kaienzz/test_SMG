<?php

namespace App\Application\DTOs;

use App\Models\Character;

/**
 * ゲーム画面用のView用データ統一DTO
 * 
 * GameDisplayService から返されるデータを型安全に管理
 * Blade テンプレートと JavaScript の両方に対応
 */
class GameViewData
{
    public function __construct(
        public readonly Character $character,
        public readonly LocationData $currentLocation,
        public readonly ?LocationData $nextLocation,
        public readonly PlayerData $player,
        public readonly MovementInfo $movementInfo,
        public readonly LocationStatus $locationStatus
    ) {}

    /**
     * ファクトリーメソッド: Character から GameViewData を作成
     *
     * @param Character $character
     * @param LocationData $currentLocation
     * @param LocationData|null $nextLocation
     * @param array $locationStatus
     * @return self
     */
    public static function create(
        Character $character,
        LocationData $currentLocation,
        ?LocationData $nextLocation,
        array $locationStatus
    ): self {
        $playerData = PlayerData::fromCharacter($character, $locationStatus);
        $movementInfo = MovementInfo::getDefault($character);
        $locationStatusDto = LocationStatus::fromArray($locationStatus);

        return new self(
            character: $character,
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            player: $playerData,
            movementInfo: $movementInfo,
            locationStatus: $locationStatusDto
        );
    }

    /**
     * Blade テンプレート用の配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'character' => $this->character,
            'player' => $this->player->toObject(), // 既存テンプレート互換性
            'currentLocation' => $this->currentLocation->toObject(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'movementInfo' => $this->movementInfo->toArray(),
            'locationStatus' => $this->locationStatus->toArray(),
        ];
    }

    /**
     * JavaScript 用の JSON に変換
     *
     * @return array
     */
    public function toJson(): array
    {
        return [
            'character' => [
                'id' => $this->character->id,
                'name' => $this->character->name,
                'location_type' => $this->character->location_type,
                'location_id' => $this->character->location_id,
                'game_position' => $this->character->game_position,
                'hp' => $this->character->hp,
                'max_hp' => $this->character->max_hp,
                'sp' => $this->character->sp,
                'max_sp' => $this->character->max_sp,
                'gold' => $this->character->gold,
            ],
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'position' => $this->character->game_position ?? 0,
            'location_type' => $this->character->location_type ?? 'town',
            'isInTown' => $this->locationStatus->isInTown,
            'isOnRoad' => $this->locationStatus->isOnRoad,
            'canMove' => $this->locationStatus->canMove,
            'movementInfo' => $this->movementInfo->toArray(),
        ];
    }

    /**
     * Ajax レスポンス用データを取得
     *
     * @return array
     */
    public function toAjaxResponse(): array
    {
        return [
            'success' => true,
            'data' => $this->toJson(),
        ];
    }

    /**
     * バリデーション: 必須データの確認
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->character !== null
            && $this->currentLocation !== null
            && $this->player !== null
            && $this->movementInfo !== null
            && $this->locationStatus !== null;
    }

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            'GameViewData[character=%s, location=%s, position=%d]',
            $this->character->name,
            $this->currentLocation->name,
            $this->character->game_position ?? 0
        );
    }
}

/**
 * 位置情報DTO
 */
class LocationData
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            id: $data['id'],
            name: $data['name']
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function toObject(): object
    {
        return (object) $this->toArray();
    }
}

/**
 * プレイヤーデータDTO（既存テンプレート互換性）
 */
class PlayerData
{
    public function __construct(
        public readonly string $name,
        public readonly string $current_location_type,
        public readonly string $current_location_id,
        public readonly int $position,
        public readonly int $game_position,
        private readonly array $locationStatus
    ) {}

    public static function fromCharacter(Character $character, array $locationStatus): self
    {
        return new self(
            name: $character->name ?? 'プレイヤー',
            current_location_type: $character->location_type ?? 'town',
            current_location_id: $character->location_id ?? 'town_a',
            position: $character->game_position ?? 0,
            game_position: $character->game_position ?? 0,
            locationStatus: $locationStatus
        );
    }

    public function toObject(): object
    {
        return (object) [
            'name' => $this->name,
            'current_location_type' => $this->current_location_type,
            'current_location_id' => $this->current_location_id,
            'position' => $this->position,
            'game_position' => $this->game_position,
            
            // 既存テンプレート互換性のためのメソッド
            'isInTown' => fn() => $this->locationStatus['isInTown'],
            'isOnRoad' => fn() => $this->locationStatus['isOnRoad'],
            'getCharacter' => fn() => null, // TODO: 実装が必要な場合
        ];
    }
}

/**
 * 移動情報DTO
 */
class MovementInfo
{
    public function __construct(
        public readonly int $base_dice_count,
        public readonly int $extra_dice,
        public readonly int $total_dice_count,
        public readonly int $dice_bonus,
        public readonly float $movement_multiplier,
        public readonly array $special_effects,
        public readonly int $min_possible_movement,
        public readonly int $max_possible_movement
    ) {}

    public static function getDefault(Character $character): self
    {
        // TODO: 将来的には Character のスキル・装備から動的計算
        return new self(
            base_dice_count: 2,
            extra_dice: 1,
            total_dice_count: 3,
            dice_bonus: 3,
            movement_multiplier: 1.0,
            special_effects: [],
            min_possible_movement: 6,
            max_possible_movement: 21
        );
    }

    public function toArray(): array
    {
        return [
            'base_dice_count' => $this->base_dice_count,
            'extra_dice' => $this->extra_dice,
            'total_dice_count' => $this->total_dice_count,
            'dice_bonus' => $this->dice_bonus,
            'movement_multiplier' => $this->movement_multiplier,
            'special_effects' => $this->special_effects,
            'min_possible_movement' => $this->min_possible_movement,
            'max_possible_movement' => $this->max_possible_movement,
        ];
    }
}

/**
 * 位置状態DTO
 */
class LocationStatus
{
    public function __construct(
        public readonly bool $isInTown,
        public readonly bool $isOnRoad,
        public readonly bool $canMove
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isInTown: $data['isInTown'],
            isOnRoad: $data['isOnRoad'],
            canMove: $data['canMove']
        );
    }

    public function toArray(): array
    {
        return [
            'isInTown' => $this->isInTown,
            'isOnRoad' => $this->isOnRoad,
            'canMove' => $this->canMove,
        ];
    }
}