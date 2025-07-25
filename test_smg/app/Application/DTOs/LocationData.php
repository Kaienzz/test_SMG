<?php

namespace App\Application\DTOs;

/**
 * 位置情報DTO
 * 
 * ゲーム内の場所（町、道路、ダンジョン等）の情報を統一管理
 * GameViewData から独立したクラスとして作成
 */
class LocationData
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly string $name
    ) {}

    /**
     * 配列からLocationDataを作成
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            id: $data['id'],
            name: $data['name']
        );
    }

    /**
     * 配列に変換
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    /**
     * オブジェクトに変換（Blade テンプレート用）
     *
     * @return object
     */
    public function toObject(): object
    {
        return (object) $this->toArray();
    }

    /**
     * 町かどうか判定
     *
     * @return bool
     */
    public function isTown(): bool
    {
        return $this->type === 'town';
    }

    /**
     * 道路かどうか判定
     *
     * @return bool
     */
    public function isRoad(): bool
    {
        return $this->type === 'road';
    }

    /**
     * ダンジョンかどうか判定
     *
     * @return bool
     */
    public function isDungeon(): bool
    {
        return $this->type === 'dungeon';
    }

    /**
     * デバッグ用の文字列表現
     *
     * @return string
     */
    public function __toString(): string
    {
        return "LocationData[{$this->type}:{$this->id} - {$this->name}]";
    }
}