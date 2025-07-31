<?php

namespace App\Application\DTOs;

use App\Models\Player;

/**
 * ゲーム画面用のView用データ統一DTO
 * 
 * GameDisplayService から返されるデータを型安全に管理
 * Blade テンプレートと JavaScript の両方に対応
 */
class GameViewData
{
    public function __construct(
        public readonly Player $player,
        public readonly LocationData $currentLocation,
        public readonly ?LocationData $nextLocation,
        public readonly PlayerData $playerData,
        public readonly MovementInfo $movementInfo,
        public readonly LocationStatus $locationStatus
    ) {}

    /**
     * ファクトリーメソッド: Player から GameViewData を作成
     *
     * @param Player $player
     * @param LocationData $currentLocation
     * @param LocationData|null $nextLocation
     * @param array $locationStatus
     * @return self
     */
    public static function create(
        Player $player,
        LocationData $currentLocation,
        ?LocationData $nextLocation,
        array $locationStatus
    ): self {
        $playerData = PlayerData::fromPlayer($player, $locationStatus);
        $movementInfo = MovementInfo::getDefault($player);
        $locationStatusDto = LocationStatus::fromArray($locationStatus);

        return new self(
            player: $player,
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            playerData: $playerData,
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
            'player' => $this->player, // Player model instance for templates
            'character' => $this->player, // 下位互換性のためのalias
            'currentLocation' => $this->currentLocation->toObject(),
            'nextLocation' => $this->nextLocation?->toObject(),
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
            'player' => [
                'id' => $this->player->id,
                'name' => $this->player->name,
                'location_type' => $this->player->location_type,
                'location_id' => $this->player->location_id,
                'game_position' => $this->player->game_position,
                'hp' => $this->player->hp,
                'max_hp' => $this->player->max_hp,
                'sp' => $this->player->sp,
                'max_sp' => $this->player->max_sp,
                'gold' => $this->player->gold,
            ],
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'position' => $this->player->game_position ?? 0,
            'location_type' => $this->player->location_type ?? 'town',
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
        return $this->player !== null
            && $this->currentLocation !== null
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
            'GameViewData[player=%s, location=%s, position=%d]',
            $this->player->name,
            $this->currentLocation->name,
            $this->player->game_position ?? 0
        );
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

    public static function fromPlayer(Player $player, array $locationStatus): self
    {
        return new self(
            name: $player->name ?? 'プレイヤー',
            current_location_type: $player->location_type ?? 'town',
            current_location_id: $player->location_id ?? 'town_a',
            position: $player->game_position ?? 0,
            game_position: $player->game_position ?? 0,
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
            'getPlayer' => fn() => null, // TODO: 実装が必要な場合
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

    public static function getDefault(Player $player): self
    {
        // TODO: 将来的には Player のスキル・装備から動的計算
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