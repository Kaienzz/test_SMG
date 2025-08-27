<?php

namespace App\Services;

use App\Models\TownFacility;
use App\Models\Player;
use App\Enums\FacilityType;

class BlacksmithFacilityService extends AbstractFacilityService
{
    public function __construct()
    {
        parent::__construct(FacilityType::BLACKSMITH);
    }

    public function getAvailableServices(TownFacility $facility): array
    {
        $config = $facility->facility_config;
        
        return [
            'repair' => [
                'name' => '修理',
                'description' => '武器・防具を修理します',
                'base_cost' => $config['repair_base_cost'] ?? 50,
                'available' => true,
            ],
            'enhance' => [
                'name' => '強化',
                'description' => '武器・防具を強化します',
                'base_cost' => $config['enhance_base_cost'] ?? 100,
                'available' => $config['enhance_available'] ?? true,
            ],
            'dismantle' => [
                'name' => '解体',
                'description' => '装備を素材に解体します',
                'cost' => $config['dismantle_cost'] ?? 20,
                'available' => $config['dismantle_available'] ?? false,
            ],
        ];
    }

    public function processTransaction(TownFacility $facility, Player $player, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効なリクエストです。');
        }

        $serviceType = $data['service_type'];
        $itemSlot = $data['item_slot'] ?? null;

        switch ($serviceType) {
            case 'repair':
                return $this->repairItem($facility, $player, $itemSlot);
            case 'enhance':
                return $this->enhanceItem($facility, $player, $itemSlot);
            case 'dismantle':
                return $this->dismantleItem($facility, $player, $itemSlot);
            default:
                return $this->createErrorResponse('不明なサービスです。');
        }
    }

    public function validateTransactionData(array $data): bool
    {
        return isset($data['service_type']) && 
               in_array($data['service_type'], ['repair', 'enhance', 'dismantle']) &&
               isset($data['item_slot']) &&
               is_numeric($data['item_slot']);
    }

    private function repairItem(TownFacility $facility, Player $player, int $itemSlot): array
    {
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();

        if (!isset($inventoryData['slots'][$itemSlot]) || 
            isset($inventoryData['slots'][$itemSlot]['empty'])) {
            return $this->createErrorResponse('そのスロットにはアイテムがありません。');
        }

        $slot = $inventoryData['slots'][$itemSlot];
        $itemInfo = $slot['item_info'] ?? [];

        // 武器・防具のみ修理可能
        if (!in_array($itemInfo['category'] ?? '', ['weapon', 'armor'])) {
            return $this->createErrorResponse('武器・防具のみ修理できます。');
        }

        $currentDurability = $slot['durability'] ?? $itemInfo['max_durability'] ?? 100;
        $maxDurability = $itemInfo['max_durability'] ?? 100;

        if ($currentDurability >= $maxDurability) {
            return $this->createErrorResponse('このアイテムは修理不要です。');
        }

        $repairCost = $facility->facility_config['repair_base_cost'] ?? 50;
        $actualCost = (int) ($repairCost * (($maxDurability - $currentDurability) / 100));

        if ($player->gold < $actualCost) {
            return $this->createErrorResponse("お金が足りません。必要金額: {$actualCost}G");
        }

        // 修理実行
        $inventorySlots = $inventory->getSlotData();
        $inventorySlots[$itemSlot]['durability'] = $maxDurability;
        $inventory->setSlotData($inventorySlots);
        $inventory->save();

        $player->gold -= $actualCost;
        $player->save();

        return $this->createSuccessResponse(
            "{$itemInfo['name']}を修理しました。",
            [
                'item_name' => $itemInfo['name'],
                'cost' => $actualCost,
                'durability' => $maxDurability,
                'remaining_gold' => $player->gold,
            ]
        );
    }

    private function enhanceItem(TownFacility $facility, Player $player, int $itemSlot): array
    {
        // 強化処理は将来実装
        return $this->createErrorResponse('強化機能は現在開発中です。');
    }

    private function dismantleItem(TownFacility $facility, Player $player, int $itemSlot): array
    {
        // 解体処理は将来実装
        return $this->createErrorResponse('解体機能は現在開発中です。');
    }

    protected function getItemTypeFilter(): array
    {
        return ['weapon', 'armor'];
    }
}