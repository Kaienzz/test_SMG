<?php

namespace App\Application\DTOs;

use App\Models\Player;
use App\Models\Monster;

/**
 * 移動結果統一DTO
 * 
 * GameController の move, moveToNext メソッドの戻り値を型安全に管理
 * Ajax レスポンス形式の統一とエンカウント情報を包含
 */
class MoveResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $position,
        public readonly int $steps_moved,
        public readonly LocationData $currentLocation,
        public readonly ?LocationData $nextLocation,
        public readonly bool $canMoveToNext,
        public readonly bool $canMoveToPrevious,
        public readonly ?EncounterData $encounter = null,
        public readonly ?string $message = null,
        public readonly ?string $error = null
    ) {}

    /**
     * 成功した移動結果を作成
     *
     * @param int $position
     * @param int $stepsMoved
     * @param LocationData $currentLocation
     * @param LocationData|null $nextLocation
     * @param bool $canMoveToNext
     * @param bool $canMoveToPrevious
     * @param EncounterData|null $encounter
     * @return self
     */
    public static function success(
        int $position,
        int $stepsMoved,
        LocationData $currentLocation,
        ?LocationData $nextLocation,
        bool $canMoveToNext,
        bool $canMoveToPrevious,
        ?EncounterData $encounter = null
    ): self {
        return new self(
            success: true,
            position: $position,
            steps_moved: $stepsMoved,
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            canMoveToNext: $canMoveToNext,
            canMoveToPrevious: $canMoveToPrevious,
            encounter: $encounter
        );
    }

    /**
     * 失敗した移動結果を作成
     *
     * @param string $error
     * @param int $currentPosition
     * @param LocationData $currentLocation
     * @return self
     */
    public static function failure(
        string $error,
        int $currentPosition,
        LocationData $currentLocation
    ): self {
        return new self(
            success: false,
            position: $currentPosition,
            steps_moved: 0,
            currentLocation: $currentLocation,
            nextLocation: null,
            canMoveToNext: false,
            canMoveToPrevious: false,
            error: $error
        );
    }

    /**
     * 位置遷移成功結果を作成（moveToNext用）
     *
     * @param LocationData $currentLocation
     * @param LocationData|null $nextLocation
     * @param int $position
     * @param string $message
     * @return self
     */
    public static function transition(
        LocationData $currentLocation,
        ?LocationData $nextLocation,
        int $position,
        string $message = '移動しました'
    ): self {
        return new self(
            success: true,
            position: $position,
            steps_moved: 0,
            currentLocation: $currentLocation,
            nextLocation: $nextLocation,
            canMoveToNext: false,
            canMoveToPrevious: false,
            message: $message
        );
    }

    /**
     * Ajax レスポンス用の配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'position' => $this->position,
            'steps_moved' => $this->steps_moved,
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'canMoveToNext' => $this->canMoveToNext,
            'canMoveToPrevious' => $this->canMoveToPrevious,
        ];

        if ($this->encounter) {
            $result['encounter'] = true;
            $result['monster'] = $this->encounter->toArray();
        }

        if ($this->message) {
            $result['message'] = $this->message;
        }

        if ($this->error) {
            $result['error'] = $this->error;
        }

        return $result;
    }

    /**
     * JSON レスポンス用データを取得
     *
     * @return array
     */
    public function toJsonResponse(): array
    {
        return $this->toArray();
    }

    /**
     * HTTP ステータスコードを取得
     *
     * @return int
     */
    public function getHttpStatus(): int
    {
        return $this->success ? 200 : 400;
    }

    /**
     * エンカウントが発生したかどうか
     *
     * @return bool
     */
    public function hasEncounter(): bool
    {
        return $this->encounter !== null;
    }

    /**
     * エラーが発生したかどうか
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !$this->success || $this->error !== null;
    }

    /**
     * 位置が境界（0、50、100）に達したかどうか
     *
     * @return bool
     */
    public function isAtBoundary(): bool
    {
        return $this->position === 0 || $this->position === 50 || $this->position === 100;
    }

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->success) {
            return "MoveResult[FAILED: {$this->error}]";
        }

        $encounterText = $this->hasEncounter() ? ' +ENCOUNTER' : '';
        return "MoveResult[{$this->position}, moved={$this->steps_moved}{$encounterText}]";
    }
}


/**
 * 場所遷移結果DTO
 */
class LocationTransitionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly LocationData $currentLocation,
        public readonly ?LocationData $nextLocation,
        public readonly int $position,
        public readonly string $location_type,
        public readonly ?string $message = null
    ) {}

    /**
     * 配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'currentLocation' => $this->currentLocation->toArray(),
            'nextLocation' => $this->nextLocation?->toArray(),
            'position' => $this->position,
            'location_type' => $this->location_type,
            'message' => $this->message,
        ];
    }

    /**
     * JSON レスポンス用データを取得
     *
     * @return array
     */
    public function toJsonResponse(): array
    {
        return $this->toArray();
    }
}