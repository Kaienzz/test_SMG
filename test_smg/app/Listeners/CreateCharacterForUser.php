<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Character;
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
        
        // キャラクターを作成
        $character = Character::getOrCreateForUser($user->id);
        
        // インベントリを作成
        Inventory::createForCharacter($character->id);
        
        // 装備を作成
        Equipment::createForCharacter($character->id);
        
        // 初期スキルを習得（オプション）
        $character->learnSkill('combat', '基本攻撃', [
            'damage_bonus' => 5,
            'accuracy_bonus' => 10
        ], 5, 0);
        
        $character->learnSkill('movement', '移動', [
            'agility_bonus' => 3
        ], 3, 0);
    }
}
