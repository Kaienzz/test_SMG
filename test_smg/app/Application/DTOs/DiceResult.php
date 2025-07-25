<?php

namespace App\Application\DTOs;

/**
 * サイコロ結果統一DTO
 * 
 * GameController の rollDice メソッドの戻り値を型安全に管理
 * Ajax レスポンス形式の統一とフロントエンド連携を担当
 */
class DiceResult
{
    public function __construct(
        public readonly array $dice_rolls,
        public readonly int $dice_count,
        public readonly int $base_total,
        public readonly int $bonus,
        public readonly int $final_movement,
        public readonly array $movement_effects,
        public readonly string $rolled_at
    ) {}

    /**
     * サイコロ結果を作成
     *
     * @param array $diceRolls
     * @param int $bonus
     * @param array $movementEffects
     * @return self
     */
    public static function create(
        array $diceRolls,
        int $bonus = 0,
        array $movementEffects = []
    ): self {
        $baseTotal = array_sum($diceRolls);
        $finalMovement = $baseTotal + $bonus;
        
        return new self(
            dice_rolls: $diceRolls,
            dice_count: count($diceRolls),
            base_total: $baseTotal,
            bonus: $bonus,
            final_movement: $finalMovement,
            movement_effects: $movementEffects,
            rolled_at: now()->toISOString()
        );
    }

    /**
     * Ajax レスポンス用の配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'dice_rolls' => $this->dice_rolls,
            'dice_count' => $this->dice_count,
            'dice1' => $this->dice_rolls[0] ?? 0,
            'dice2' => $this->dice_rolls[1] ?? 0,
            'base_total' => $this->base_total,
            'bonus' => $this->bonus,
            'total' => $this->final_movement,
            'final_movement' => $this->final_movement,
            'movement_effects' => $this->movement_effects,
            'rolled_at' => $this->rolled_at
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

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        $diceStr = implode('+', $this->dice_rolls);
        return "DiceResult[{$diceStr} + {$this->bonus} = {$this->final_movement}]";
    }
}