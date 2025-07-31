<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Player;
use App\Models\Inventory;
use App\Models\Equipment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CreateCharacterForUser
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;
        
        // プレイヤーを作成
        $player = Player::getOrCreateForUser($user->id);
        
        // インベントリを作成
        Inventory::createForPlayer($player->id);
        
        // 装備を作成
        Equipment::createForPlayer($player->id);
        
        // 初期プレイヤースキルを習得（飛脚術と採集のみ）
        // 基本攻撃と移動は基本能力なのでスキルとして登録しない
    }
}
