<?php

namespace App\Domain\Character;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Characterクラスのインベントリ関連機能を分離したTrait
 * 
 * Phase 4: Character分割でのコード分離により単一責任原則を強化
 * インベントリシステムの複雑性をCharacterクラスから切り離し
 */
trait CharacterInventory
{
    /**
     * インベントリとのリレーション
     *
     * @return HasOne
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    /**
     * キャラクターのインベントリを取得する
     *
     * @return Inventory
     */
    public function getInventory(): Inventory
    {
        if ($this->inventory) {
            return $this->inventory;
        }

        return Inventory::createForCharacter($this->id ?? 1);
    }

    /**
     * インベントリ情報を含むキャラクター情報を取得する
     *
     * @return array
     */
    public function getCharacterWithInventory(): array
    {
        $inventory = $this->getInventory();

        return [
            'character' => $this->toArray(),
            'inventory' => $inventory->getInventoryData(),
        ];
    }
}