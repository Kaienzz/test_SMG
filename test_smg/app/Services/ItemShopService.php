<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Character;
use App\Models\Item;
use App\Enums\ShopType;

class ItemShopService extends AbstractShopService
{
    public function __construct()
    {
        parent::__construct(ShopType::ITEM_SHOP);
    }

    public function getAvailableServices(Shop $shop): array
    {
        $shopItems = $shop->availableItems()->with('item')->get();
        
        return [
            'items' => $shopItems->map(function($shopItem) {
                return [
                    'id' => $shopItem->id,
                    'item' => $shopItem->item,
                    'price' => $shopItem->price,
                    'stock' => $shopItem->stock,
                    'is_in_stock' => $shopItem->isInStock(),
                    'effects' => json_decode($shopItem->item->effects, true),
                ];
            })->toArray(),
        ];
    }

    public function getCharacterInventory(Character $character): array
    {
        $inventory = $character->getInventory();
        return $inventory->getInventoryData();
    }

    public function processTransaction(Shop $shop, Character $character, array $data): array
    {
        if (!$this->validateTransactionData($data)) {
            return $this->createErrorResponse('無効な取引データです。');
        }

        // 購入処理
        if (isset($data['shop_item_id'])) {
            return $this->processPurchase($shop, $character, $data);
        }
        
        // 売却処理
        if (isset($data['sell_slot_index'])) {
            return $this->processSale($shop, $character, $data);
        }

        return $this->createErrorResponse('無効な取引データです。');
    }

    private function processPurchase(Shop $shop, Character $character, array $data): array
    {
        $shopItem = ShopItem::with(['shop', 'item'])->find($data['shop_item_id']);
        $quantity = $data['quantity'] ?? 1;

        if (!$shopItem || !$shopItem->is_available) {
            return $this->createErrorResponse('このアイテムは販売されていません。');
        }

        if (!$shopItem->isInStock()) {
            return $this->createErrorResponse('このアイテムは在庫切れです。');
        }

        $totalPrice = $shopItem->price * $quantity;

        if (!$character->hasGold($totalPrice)) {
            return $this->createErrorResponse(
                "Gが足りません。必要: {$totalPrice}G, 所持: {$character->gold}G"
            );
        }

        if (!$shopItem->decreaseStock($quantity)) {
            return $this->createErrorResponse('在庫が不足しています。');
        }

        if (!$character->spendGold($totalPrice)) {
            $shopItem->stock += $quantity;
            $shopItem->save();
            
            return $this->createErrorResponse('Gの支払いに失敗しました。');
        }

        $itemService = new ItemService();
        $addResult = $itemService->addItemToInventory($character->id, $shopItem->item_id, $quantity);

        if (!$addResult['success']) {
            $shopItem->stock += $quantity;
            $shopItem->save();
            $character->addGold($totalPrice);
            
            return $this->createErrorResponse($addResult['message']);
        }

        $character->save();

        $this->logTransaction($shop, $character, 'purchase', [
            'item_id' => $shopItem->item_id,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);

        return $this->createSuccessResponse(
            "{$shopItem->item->name} x{$quantity} を {$totalPrice}G で購入しました。",
            [
                'item' => [
                    'name' => $shopItem->item->name,
                    'quantity' => $quantity,
                    'total_price' => $totalPrice,
                ],
                'character' => [
                    'remaining_gold' => $character->gold,
                ],
                'shop_item' => [
                    'remaining_stock' => $shopItem->stock,
                    'is_in_stock' => $shopItem->isInStock(),
                ],
            ]
        );
    }

    private function processSale(Shop $shop, Character $character, array $data): array
    {
        $slotIndex = $data['sell_slot_index'];
        $quantity = $data['quantity'] ?? 1;

        $inventory = $character->getInventory();
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
        $character->addGold($totalSellPrice);
        $character->save();
        $inventory->save();

        $this->logTransaction($shop, $character, 'sale', [
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
                'character' => [
                    'remaining_gold' => $character->gold,
                ],
                'inventory' => $inventory->getInventoryData(),
            ]
        );
    }

    public function validateTransactionData(array $data): bool
    {
        // 購入処理
        if (isset($data['shop_item_id'])) {
            return is_numeric($data['shop_item_id']) &&
                   (!isset($data['quantity']) || (is_numeric($data['quantity']) && $data['quantity'] >= 1 && $data['quantity'] <= 99));
        }
        
        // 売却処理
        if (isset($data['sell_slot_index'])) {
            return is_numeric($data['sell_slot_index']) &&
                   (!isset($data['quantity']) || (is_numeric($data['quantity']) && $data['quantity'] >= 1 && $data['quantity'] <= 99));
        }

        return false;
    }
}