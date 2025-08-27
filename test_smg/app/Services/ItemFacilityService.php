<?php

namespace App\Services;

use App\Models\TownFacility;
use App\Models\FacilityItem;
use App\Models\Player;
use App\Models\Item;
use App\Enums\FacilityType;

class ItemFacilityService extends AbstractFacilityService
{
    public function __construct()
    {
        parent::__construct(FacilityType::ITEM_SHOP);
    }

    public function getAvailableServices(TownFacility $facility): array
    {
        $facilityItems = $facility->availableItems()->with('item')->get();
        
        return [
            'items' => $facilityItems->map(function($facilityItem) {
                return [
                    'id' => $facilityItem->id,
                    'item' => $facilityItem->item,
                    'price' => $facilityItem->price,
                    'stock' => $facilityItem->stock,
                    'is_in_stock' => $facilityItem->isInStock(),
                    'effects' => is_string($facilityItem->item->effects) ? json_decode($facilityItem->item->effects, true) : $facilityItem->item->effects,
                ];
            })->toArray(),
        ];
    }

    public function getPlayerInventory(Player $player): array
    {
        $inventory = $player->getInventory();
        return $inventory->getInventoryData();
    }

    public function processTransaction(TownFacility $facility, Player $player, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効な取引データです。');
        }

        // 購入処理
        if (isset($data['facility_item_id'])) {
            return $this->processPurchase($facility, $player, $data);
        }
        
        // 売却処理
        if (isset($data['sell_slot_index'])) {
            return $this->processSale($facility, $player, $data);
        }

        return $this->createErrorResponse('無効な取引データです。');
    }

    private function processPurchase(TownFacility $facility, Player $player, array $data): array
    {
        $facilityItem = FacilityItem::with(['facility', 'item'])->find($data['facility_item_id']);
        $quantity = $data['quantity'] ?? 1;

        if (!$facilityItem || !$facilityItem->is_available) {
            return $this->createErrorResponse('このアイテムは販売されていません。');
        }

        if (!$facilityItem->isInStock()) {
            return $this->createErrorResponse('このアイテムは在庫切れです。');
        }

        $totalPrice = $facilityItem->price * $quantity;

        if (!$player->hasGold($totalPrice)) {
            return $this->createErrorResponse(
                "Gが足りません。必要: {$totalPrice}G, 所持: {$player->gold}G"
            );
        }

        if (!$facilityItem->decreaseStock($quantity)) {
            return $this->createErrorResponse('在庫が不足しています。');
        }

        if (!$player->spendGold($totalPrice)) {
            $facilityItem->stock += $quantity;
            $facilityItem->save();
            
            return $this->createErrorResponse('Gの支払いに失敗しました。');
        }

        $itemService = new ItemService();
        $addResult = $itemService->addItemToInventory($player->id, $facilityItem->item_id, $quantity);

        if (!$addResult['success']) {
            $facilityItem->stock += $quantity;
            $facilityItem->save();
            $player->addGold($totalPrice);
            
            return $this->createErrorResponse($addResult['message']);
        }

        $player->save();

        $this->logTransaction($facility, $player, 'purchase', [
            'item_id' => $facilityItem->item_id,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);

        return $this->createSuccessResponse(
            "{$facilityItem->item->name} x{$quantity} を {$totalPrice}G で購入しました。",
            [
                'item' => [
                    'name' => $facilityItem->item->name,
                    'quantity' => $quantity,
                    'total_price' => $totalPrice,
                ],
                'player' => [
                    'remaining_gold' => $player->gold,
                ],
                'facility_item' => [
                    'remaining_stock' => $facilityItem->stock,
                    'is_in_stock' => $facilityItem->isInStock(),
                ],
            ]
        );
    }

    private function processSale(TownFacility $facility, Player $player, array $data): array
    {
        $slotIndex = $data['sell_slot_index'];
        $quantity = $data['quantity'] ?? 1;

        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();

        if (!isset($inventoryData['slots'][$slotIndex]) || 
            isset($inventoryData['slots'][$slotIndex]['empty'])) {
            return $this->createErrorResponse('そのスロットにはアイテムがありません。');
        }

        $slot = $inventoryData['slots'][$slotIndex];
        
        if ($slot['quantity'] < $quantity) {
            return $this->createErrorResponse('売却する数量が不足しています。');
        }

        $item = Item::findSampleItem($slot['item_name']);
        if (!$item) {
            return $this->createErrorResponse('売却できないアイテムです。');
        }

        $sellPrice = $item->getSellPrice();
        $totalSellPrice = $sellPrice * $quantity;

        // インベントリからアイテムを削除
        $removeResult = $inventory->removeItem($slotIndex, $quantity);
        
        if (!$removeResult['success']) {
            return $this->createErrorResponse($removeResult['message']);
        }

        // ゴールドを追加
        $player->addGold($totalSellPrice);
        $player->save();
        $inventory->save();

        $this->logTransaction($facility, $player, 'sale', [
            'item_name' => $item->name,
            'quantity' => $quantity,
            'total_price' => $totalSellPrice,
        ]);

        return $this->createSuccessResponse(
            "{$item->name} x{$quantity} を {$totalSellPrice}G で売却しました。",
            [
                'item' => [
                    'name' => $item->name,
                    'quantity' => $quantity,
                    'total_price' => $totalSellPrice,
                ],
                'player' => [
                    'remaining_gold' => $player->gold,
                ],
                'inventory' => $inventory->getInventoryData(),
            ]
        );
    }

    public function validateTransactionData(array $data): bool
    {
        // 購入処理
        if (isset($data['facility_item_id'])) {
            return is_numeric($data['facility_item_id']) &&
                   (!isset($data['quantity']) || (is_numeric($data['quantity']) && $data['quantity'] >= 1 && $data['quantity'] <= 99));
        }
        
        // 売却処理
        if (isset($data['sell_slot_index'])) {
            return is_numeric($data['sell_slot_index']) &&
                   (!isset($data['quantity']) || (is_numeric($data['quantity']) && $data['quantity'] >= 1 && $data['quantity'] <= 99));
        }

        return false;
    }

    protected function logTransaction(TownFacility $facility, Player $player, string $transactionType, array $data): void
    {
        // TODO: 将来的にトランザクションログを実装する場合はここに処理を追加
        // 現在はプレースホルダーとして空のメソッドを提供
    }
}