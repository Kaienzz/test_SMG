<?php

namespace App\Domain\Character;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Characterクラスの装備関連機能を分離したTrait
 * 
 * Phase 4: Character分割でのコード分離により単一責任原則を強化
 * 装備システムの複雑性をCharacterクラスから切り離し
 */
trait CharacterEquipment
{
    /**
     * 装備とのリレーション
     *
     * @return HasOne
     */
    public function equipment(): HasOne
    {
        return $this->hasOne(Equipment::class);
    }

    /**
     * キャラクターの装備を取得する
     *
     * @return Equipment
     */
    public function getEquipment(): Equipment
    {
        if ($this->equipment) {
            return $this->equipment;
        }

        return Equipment::createForCharacter($this->id ?? 1);
    }

    /**
     * 装備を取得または作成する
     *
     * @return Equipment
     */
    public function getOrCreateEquipment(): Equipment
    {
        return $this->equipment ?: Equipment::createForCharacter($this->id);
    }

    /**
     * 装備を含む総ステータスを取得する
     *
     * @return array
     */
    public function getTotalStatsWithEquipment(): array
    {
        // ベースステータスを取得
        $baseStats = $this->getBaseStats();
        $equipment = $this->getOrCreateEquipment();
        $equipmentStats = $equipment->getTotalStats();

        return [
            'attack' => ($baseStats['attack'] ?? 0) + ($equipmentStats['attack'] ?? 0),
            'defense' => ($baseStats['defense'] ?? 0) + ($equipmentStats['defense'] ?? 0),
            'agility' => ($baseStats['agility'] ?? 0) + ($equipmentStats['agility'] ?? 0),
            'evasion' => ($baseStats['evasion'] ?? 0) + ($equipmentStats['evasion'] ?? 0),
            'magic_attack' => ($baseStats['magic_attack'] ?? 0) + ($equipmentStats['magic_attack'] ?? 0),
            'max_hp' => ($baseStats['max_hp'] ?? 0) + ($equipmentStats['hp'] ?? 0),
            'max_sp' => ($baseStats['max_sp'] ?? 0) + ($equipmentStats['sp'] ?? 0),
            'max_mp' => ($baseStats['max_mp'] ?? 0) + ($equipmentStats['mp'] ?? 0),
            'accuracy' => ($baseStats['accuracy'] ?? 0) + ($equipmentStats['accuracy'] ?? 0),
            'equipment_effects' => $equipmentStats['effects'] ?? [],
        ];
    }

    /**
     * 装備情報を含むキャラクター情報を取得する
     *
     * @return array
     */
    public function getCharacterWithEquipment(): array
    {
        $inventory = $this->getInventory();
        $equipment = $this->getEquipment();

        return [
            'character' => $this->toArray(),
            'inventory' => $inventory->getInventoryData(),
            'equipment' => $equipment->getEquippedItems(),
            'equipment_stats' => $equipment->getTotalStats(),
        ];
    }

}