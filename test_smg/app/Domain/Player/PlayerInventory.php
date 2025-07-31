<?php

namespace App\Domain\Player;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Playerクラスのインベントリ関連機能を分離したTrait
 * 
 * Phase 2: Character→Player移行によりインベントリシステムをPlayer向けに適合
 * インベントリシステムの複雑性をPlayerクラスから切り離し
 */
trait PlayerInventory
{
    /**
     * インベントリとのリレーション
     *
     * @return HasOne
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'player_id');
    }

    /**
     * プレイヤーのインベントリを取得する
     *
     * @return Inventory
     */
    public function getInventory(): Inventory
    {
        if ($this->inventory) {
            return $this->inventory;
        }

        return Inventory::createForPlayer($this->id ?? 1);
    }

    /**
     * インベントリ情報を含むプレイヤー情報を取得する
     *
     * @return array
     */
    public function getPlayerWithInventory(): array
    {
        $inventory = $this->getInventory();

        return [
            'player' => $this->toArray(),
            'inventory' => $inventory->getInventoryData(),
        ];
    }
}