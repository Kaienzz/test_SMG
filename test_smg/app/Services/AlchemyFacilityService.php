<?php

namespace App\Services;

use App\Models\TownFacility;
use App\Models\Player;
use App\Models\Item;
use App\Models\CustomItem;
use App\Models\AlchemyMaterial;
use App\Enums\FacilityType;
use App\Contracts\FacilityServiceInterface;

class AlchemyFacilityService extends AbstractFacilityService
{
    public function __construct()
    {
        parent::__construct(FacilityType::ALCHEMY_SHOP);
    }

    public function getFacilityData(TownFacility $facility): array
    {
        $baseData = parent::getFacilityData($facility);
        $baseData['available_materials'] = $this->getAvailableMaterials();
        
        return $baseData;
    }

    public function getAvailableServices(TownFacility $facility): array
    {
        return [
            'alchemy' => [
                'name' => '錬金',
                'description' => '武器・防具を素材で強化します',
                'requirements' => '公式武器・防具 + 素材アイテム',
            ],
        ];
    }

    /**
     * 利用可能な錬金素材一覧を取得
     */
    public function getAvailableMaterials(): array
    {
        return AlchemyMaterial::all()->map(function ($material) {
            return $material->getMaterialInfo();
        })->toArray();
    }

    /**
     * プレイヤーの錬金可能なアイテムを取得
     */
    public function getAlchemizableItems(Player $player): array
    {
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        $alchemizable = [];

        foreach ($inventoryData as $slotIndex => $slot) {
            if (isset($slot['empty']) && $slot['empty']) continue;
            
            $itemInfo = $slot['item_info'] ?? [];
            
            // 公式アイテムかつ武器・防具のみ錬金可能
            if (isset($itemInfo['is_custom']) && $itemInfo['is_custom']) {
                continue; // カスタムアイテムは錬金不可
            }
            
            if (!isset($itemInfo['category']) || !in_array($itemInfo['category'], ['weapon', 'armor'])) {
                continue; // 武器・防具以外は錬金不可
            }

            $alchemizable[] = [
                'slot' => $slotIndex,
                'item' => $itemInfo,
                'quantity' => $slot['quantity'],
            ];
        }

        return $alchemizable;
    }

    /**
     * プレイヤーの素材アイテムを取得
     */
    public function getMaterialItems(Player $player): array
    {
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        $materialNames = AlchemyMaterial::pluck('item_name')->toArray();
        $materials = [];

        foreach ($inventoryData as $slotIndex => $slot) {
            if (isset($slot['empty']) && $slot['empty']) continue;
            
            $itemInfo = $slot['item_info'] ?? [];
            
            // 錬金素材として登録されているアイテムのみ
            if (in_array($itemInfo['name'] ?? '', $materialNames)) {
                $materials[] = [
                    'slot' => $slotIndex,
                    'item' => $itemInfo,
                    'quantity' => $slot['quantity'],
                ];
            }
        }

        return $materials;
    }

    /**
     * 錬金処理のメイン関数
     */
    public function processTransaction(TownFacility $facility, Player $player, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効なリクエストです。');
        }

        $baseItemSlot = $data['base_item_slot'];
        $materialSlots = $data['material_slots'] ?? [];

        // ベースアイテムの検証
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        
        if (!isset($inventoryData[$baseItemSlot]) || isset($inventoryData[$baseItemSlot]['empty'])) {
            return $this->createErrorResponse('ベースアイテムが見つかりません。');
        }

        $baseItemData = $inventoryData[$baseItemSlot];
        $baseItem = $baseItemData['item_info'] ?? [];
        
        // カスタムアイテムは錬金不可
        if (isset($baseItem['is_custom']) && $baseItem['is_custom']) {
            return $this->createErrorResponse('カスタムアイテムは錬金できません。');
        }

        // 武器・防具のみ錬金可能
        if (!in_array($baseItem['category'] ?? '', ['weapon', 'armor'])) {
            return $this->createErrorResponse('武器・防具のみ錬金できます。');
        }

        // 素材の検証と効果計算
        $materialNames = [];
        foreach ($materialSlots as $materialSlot) {
            if (!isset($inventoryData[$materialSlot]) || isset($inventoryData[$materialSlot]['empty'])) {
                return $this->createErrorResponse('素材アイテムが見つかりません。');
            }
            $materialData = $inventoryData[$materialSlot];
            $materialItem = $materialData['item_info'] ?? [];
            $materialNames[] = $materialItem['name'] ?? '';
        }

        if (empty($materialNames)) {
            return $this->createErrorResponse('素材を選択してください。');
        }

        // 素材効果を計算
        $materialEffects = AlchemyMaterial::calculateCombinedEffects($materialNames);
        
        return $this->performAlchemy($player, $baseItem, $baseItemSlot, $materialSlots, $materialEffects);
    }

    /**
     * 実際の錬金処理
     */
    private function performAlchemy(Player $player, array $baseItem, int $baseItemSlot, array $materialSlots, array $materialEffects): array
    {
        try {
            // データベーストランザクション開始
            \DB::beginTransaction();

            // 1. ベースアイテムから現在の耐久度を取得
            $inventory = $player->getInventory();
            $inventoryData = $inventory->getInventoryData();
            $baseItemData = $inventoryData[$baseItemSlot];
            $currentDurability = $baseItemData['durability'] ?? $baseItem['max_durability'] ?? 100;
            
            // 2. ベースアイテムのステータスを取得
            $baseItemRecord = Item::find($baseItem['id']);
            if (!$baseItemRecord) {
                throw new \Exception('ベースアイテムが見つかりません。');
            }

            $baseStats = $baseItemRecord->getEffects();
            
            // 3. 素材効果を適用してカスタムステータスを計算
            $customStats = $this->calculateCustomStats($baseStats, $materialEffects, $currentDurability);
            
            // 4. 名匠品判定
            $isMasterwork = $this->determineMasterwork($materialEffects['total_masterwork_chance']);
            
            // 5. カスタムアイテムマスターを作成
            $customItem = CustomItem::create([
                'base_item_id' => $baseItem['id'],
                'creator_id' => $player->id,
                'custom_stats' => $customStats['final_stats'],
                'base_stats' => $baseStats,
                'material_bonuses' => $materialEffects['combined_stats'],
                'base_durability' => $currentDurability,
                'max_durability' => $customStats['final_durability'],
                'is_masterwork' => $isMasterwork,
            ]);

            // 6. インベントリからアイテムを削除
            $inventory->removeItem($baseItemSlot, 1);
            
            foreach ($materialSlots as $materialSlot) {
                $inventory->removeItem($materialSlot, 1);
            }

            // 7. カスタムアイテムをインベントリに追加
            $customItemInfo = $customItem->getItemInfo();
            // カスタムアイテムを一時的なItemオブジェクトとして追加
            $tempCustomItem = new \stdClass();
            $tempCustomItem->id = $customItem->id;  
            $tempCustomItem->name = $customItemInfo['name'];
            $tempCustomItem->description = $customItemInfo['description'];
            $tempCustomItem->category = \App\Enums\ItemCategory::from($customItemInfo['category']);
            $tempCustomItem->effects = $customItemInfo['effects'];
            $tempCustomItem->max_durability = $customItemInfo['max_durability'];
            
            // カスタムアイテム専用の追加方法
            $inventorySlots = $inventory->getSlotData();
            $emptySlot = $inventory->findEmptySlot();
            
            if ($emptySlot !== null) {
                $inventorySlots[$emptySlot] = [
                    'item_id' => $customItem->id,
                    'item_name' => $customItemInfo['name'],
                    'quantity' => 1,
                    'durability' => $customStats['final_durability'], // マスターの最大耐久度を個別耐久度として設定
                    'category' => $customItemInfo['category'],
                    'item_info' => $customItemInfo,
                ];
                $inventory->setSlotData($inventorySlots);
                $inventory->save();
            }

            \DB::commit();

            return $this->createSuccessResponse(
                ($isMasterwork ? '【名匠品】' : '') . '錬金が成功しました！',
                [
                    'custom_item' => $customItemInfo,
                    'is_masterwork' => $isMasterwork,
                    'material_effects' => $materialEffects,
                    'final_stats' => $customStats['final_stats'],
                ]
            );

        } catch (\Exception $e) {
            \DB::rollback();
            return $this->createErrorResponse('錬金に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * カスタムステータスを計算
     */
    private function calculateCustomStats(array $baseStats, array $materialEffects, int $currentDurability): array
    {
        $finalStats = [];
        $combinedStats = $materialEffects['combined_stats'];
        $durabilityBonus = $materialEffects['combined_durability_bonus'];
        
        // 基本ステータス + 素材効果を基準値とする
        foreach ($baseStats as $stat => $value) {
            $materialBonus = $combinedStats[$stat] ?? 0;
            $baseWithMaterial = $value + $materialBonus;
            $finalStats[$stat] = $baseWithMaterial;
        }
        
        // 素材にのみ存在するステータスも追加
        foreach ($combinedStats as $stat => $value) {
            if (!isset($finalStats[$stat])) {
                $finalStats[$stat] = $value;
            }
        }
        
        // ランダム効果を適用（90-110%、名匠品なら120-150%）
        $isMasterwork = $this->determineMasterwork($materialEffects['total_masterwork_chance']);
        $multiplierRange = $isMasterwork ? [1.2, 1.5] : [0.9, 1.1];
        
        foreach ($finalStats as $stat => $value) {
            if ($value > 0) { // 正の値のみランダム効果適用
                $multiplier = $this->getRandomFloat($multiplierRange[0], $multiplierRange[1]);
                $finalStats[$stat] = max(1, (int)round($value * $multiplier));
            }
        }
        
        // 耐久度計算：現在耐久度 + 素材ボーナス
        $finalDurability = max(1, $currentDurability + $durabilityBonus);
        
        return [
            'final_stats' => $finalStats,
            'final_durability' => $finalDurability,
        ];
    }

    /**
     * 名匠品判定
     */
    private function determineMasterwork(float $masterworkChance): bool
    {
        return (mt_rand(1, 10000) / 100.0) <= $masterworkChance;
    }

    /**
     * ランダムな浮動小数点数を生成
     */
    private function getRandomFloat(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    public function validateTransactionData(array $data): bool
    {
        return isset($data['base_item_slot']) && 
               is_numeric($data['base_item_slot']) &&
               isset($data['material_slots']) && 
               is_array($data['material_slots']) &&
               !empty($data['material_slots']);
    }
}