<?php

namespace App\Http\Controllers\Traits;

use App\Models\Character;
use Illuminate\Support\Facades\Auth;

trait HasCharacter
{
    /**
     * 認証ユーザーのキャラクターを取得または作成
     */
    protected function getOrCreateCharacter(): Character
    {
        $user = Auth::user();
        return $user->getOrCreateCharacter();
    }

    /**
     * 認証ユーザーのキャラクターを取得
     * キャラクターが存在しない場合はnullを返す
     */
    protected function getCharacter(): ?Character
    {
        $user = Auth::user();
        return $user->character;
    }

    /**
     * 認証ユーザーIDを取得
     */
    protected function getUserId(): int
    {
        return Auth::id();
    }
}