<?php

namespace App\Http\Controllers\Traits;

use App\Models\Player;
use Illuminate\Support\Facades\Auth;

trait HasCharacter
{
    /**
     * 認証ユーザーのプレイヤーを取得または作成
     */
    protected function getOrCreatePlayer(): Player
    {
        $user = Auth::user();
        return $user->getOrCreatePlayer();
    }

    /**
     * 認証ユーザーのプレイヤーを取得
     * プレイヤーが存在しない場合はnullを返す
     */
    protected function getPlayer(): ?Player
    {
        $user = Auth::user();
        return $user->player;
    }

    /**
     * 下位互換性のためのgetOrCreateCharacterメソッド
     * @deprecated getOrCreatePlayer()を使用してください
     */
    protected function getOrCreateCharacter(): Player
    {
        return $this->getOrCreatePlayer();
    }

    /**
     * 下位互換性のためのgetCharacterメソッド
     * @deprecated getPlayer()を使用してください
     */
    protected function getCharacter(): ?Player
    {
        return $this->getPlayer();
    }

    /**
     * 認証ユーザーIDを取得
     */
    protected function getUserId(): int
    {
        return Auth::id();
    }
}