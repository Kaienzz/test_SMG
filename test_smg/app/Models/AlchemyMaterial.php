<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlchemyMaterial extends Model
{
    protected $fillable = [
        'item_name',
        'stat_bonuses',
        'durability_bonus',
    ];
    
    protected $casts = [
        'stat_bonuses' => 'array',
        'durability_bonus' => 'integer',
    ];

    /**
     * アイテム名で素材効果を取得
     */
    public static function getEffectsByItemName(string $itemName): ?self
    {
        return static::where('item_name', $itemName)->first();
    }

    /**
     * 素材のステータス効果を取得
     */
    public function getStatBonuses(): array
    {
        return $this->stat_bonuses ?? [];
    }

    /**
     * 耐久度ボーナスを取得
     */
    public function getDurabilityBonus(): int
    {
        return $this->durability_bonus ?? 0;
    }

    /**
     * 素材効果の合計値を計算
     */
    public function getTotalEffectPower(): int
    {
        $statBonuses = $this->getStatBonuses();
        $total = array_sum($statBonuses);
        $total += $this->getDurabilityBonus();
        
        return $total;
    }

    /**
     * 名匠品確率への影響を計算（効果値が高いほど確率アップ）
     */
    public function getMasterworkChanceBonus(): float
    {
        $totalPower = $this->getTotalEffectPower();
        
        // 効果値1につき0.5%の名匠品確率ボーナス（最大15%）
        return min(15.0, $totalPower * 0.5);
    }

    /**
     * 素材情報を配列で取得
     */
    public function getMaterialInfo(): array
    {
        return [
            'id' => $this->id,
            'item_name' => $this->item_name,
            'stat_bonuses' => $this->getStatBonuses(),
            'durability_bonus' => $this->getDurabilityBonus(),
            'total_effect_power' => $this->getTotalEffectPower(),
            'masterwork_chance_bonus' => $this->getMasterworkChanceBonus(),
        ];
    }

    /**
     * 複数の素材から合計効果を計算
     */
    public static function calculateCombinedEffects(array $materialNames): array
    {
        $combinedStats = [];
        $combinedDurability = 0;
        $totalMasterworkChance = 0;
        $usedMaterials = [];

        foreach ($materialNames as $materialName) {
            $material = static::getEffectsByItemName($materialName);
            
            if ($material) {
                $usedMaterials[] = $material->getMaterialInfo();
                
                // ステータス効果を合計
                foreach ($material->getStatBonuses() as $stat => $value) {
                    $combinedStats[$stat] = ($combinedStats[$stat] ?? 0) + $value;
                }
                
                // 耐久度ボーナスを合計
                $combinedDurability += $material->getDurabilityBonus();
                
                // 名匠品確率を合計
                $totalMasterworkChance += $material->getMasterworkChanceBonus();
            }
        }

        return [
            'combined_stats' => $combinedStats,
            'combined_durability_bonus' => $combinedDurability,
            'total_masterwork_chance' => min(50.0, $totalMasterworkChance), // 最大50%まで
            'used_materials' => $usedMaterials,
        ];
    }

    /**
     * 基本素材データを取得
     */
    public static function getBasicMaterialsData(): array
    {
        return [
            [
                'item_name' => '鉄鉱石',
                'stat_bonuses' => ['attack' => 2, 'defense' => 1],
                'durability_bonus' => 10,
            ],
            [
                'item_name' => '動物の爪',
                'stat_bonuses' => ['attack' => 3, 'defense' => 2],
                'durability_bonus' => 5,
            ],
            [
                'item_name' => 'ルビー',
                'stat_bonuses' => ['attack' => 5, 'magic_attack' => 3],
                'durability_bonus' => 0,
            ],
            [
                'item_name' => 'サファイア',
                'stat_bonuses' => ['defense' => 4, 'mp' => 10],
                'durability_bonus' => 8,
            ],
            [
                'item_name' => '魔法の粉',
                'stat_bonuses' => ['magic_attack' => 4, 'mp' => 5],
                'durability_bonus' => 3,
            ],
            [
                'item_name' => '硬い石',
                'stat_bonuses' => ['defense' => 3],
                'durability_bonus' => 15,
            ],
            [
                'item_name' => '軽い羽根',
                'stat_bonuses' => ['agility' => 5, 'evasion' => 3],
                'durability_bonus' => -5,
            ],
            [
                'item_name' => '光る水晶',
                'stat_bonuses' => ['accuracy' => 8, 'magic_attack' => 2],
                'durability_bonus' => 5,
            ],
        ];
    }
}
