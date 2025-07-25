<?php

namespace App\Application\DTOs;

/**
 * 戦闘結果統一DTO
 * 
 * BattleController の各アクション結果を型安全に管理
 * Ajax レスポンス形式の統一と戦闘状態管理を担当
 */
class BattleResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $battle_end,
        public readonly array $character,
        public readonly array $monster,
        public readonly array $battle_log,
        public readonly int $turn,
        public readonly ?string $message = null,
        public readonly ?string $result = null,
        public readonly ?string $battle_id = null
    ) {}

    /**
     * 成功した戦闘アクション結果を作成
     *
     * @param array $character
     * @param array $monster
     * @param array $battleLog
     * @param int $turn
     * @param bool $battleEnd
     * @param string|null $message
     * @param string|null $result
     * @return self
     */
    public static function success(
        array $character,
        array $monster,
        array $battleLog,
        int $turn,
        bool $battleEnd = false,
        ?string $message = null,
        ?string $result = null
    ): self {
        return new self(
            success: true,
            battle_end: $battleEnd,
            character: $character,
            monster: $monster,
            battle_log: $battleLog,
            turn: $turn,
            message: $message,
            result: $result
        );
    }

    /**
     * 戦闘開始結果を作成
     *
     * @param string $battleId
     * @param array $character
     * @param array $monster
     * @param string $message
     * @return self
     */
    public static function battleStart(
        string $battleId,
        array $character,
        array $monster,
        string $message
    ): self {
        return new self(
            success: true,
            battle_end: false,
            character: $character,
            monster: $monster,
            battle_log: [],
            turn: 1,
            message: $message,
            battle_id: $battleId
        );
    }

    /**
     * 失敗した戦闘アクション結果を作成
     *
     * @param string $message
     * @return self
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            battle_end: false,
            character: [],
            monster: [],
            battle_log: [],
            turn: 0,
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
            'battle_end' => $this->battle_end,
            'character' => $this->character,
            'monster' => $this->monster,
            'battle_log' => $this->battle_log,
            'turn' => $this->turn,
        ];

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        if ($this->result !== null) {
            $result['result'] = $this->result;
        }

        if ($this->battle_id !== null) {
            $result['battle_id'] = $this->battle_id;
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
     * 戦闘が終了したかどうか
     *
     * @return bool
     */
    public function isBattleEnd(): bool
    {
        return $this->battle_end;
    }

    /**
     * エラーが発生したかどうか
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !$this->success;
    }

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->success) {
            return "BattleResult[FAILED: {$this->message}]";
        }

        $endText = $this->battle_end ? ' END' : '';
        return "BattleResult[Turn {$this->turn}{$endText}]";
    }
}