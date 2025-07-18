<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\Item;
use App\Enums\ItemCategory;
use App\Services\DummyDataService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $character = (object) DummyDataService::getCharacter(1);
        $inventoryData = DummyDataService::getInventory(1);
        
        // ダミーサンプルアイテム（カテゴリは文字列として扱う）
        $sampleItems = [
            [
                'name' => '薬草',
                'category' => 'potion',
                'category_name' => 'ポーション',
            ],
            [
                'name' => '鉄の剣',
                'category' => 'weapon',
                'category_name' => '武器',
            ],
            [
                'name' => '革の鎧',
                'category' => 'body_equipment',
                'category_name' => '胴体装備',
            ],
        ];
        
        return view('inventory.index', [
            'character' => $character,
            'inventoryData' => $inventoryData,
            'categories' => [],
            'sampleItems' => $sampleItems,
        ]);
    }

    public function show(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        return response()->json([
            'inventory' => $inventory->getInventoryData(),
            'character' => $character->getStatusSummary(),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'item_name' => 'required|string',
            'quantity' => 'integer|min:1|max:999',
        ]);

        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $item = Item::findSampleItem($request->input('item_name'));
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'アイテムが見つかりません',
            ]);
        }

        $quantity = $request->input('quantity', 1);
        $result = $inventory->addItem($item, $quantity);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'inventory' => $inventory->getInventoryData(),
            'added_quantity' => $result['added_quantity'],
            'remaining_quantity' => $result['remaining_quantity'],
        ]);
    }

    public function removeItem(Request $request): JsonResponse
    {
        $request->validate([
            'slot_index' => 'required|integer|min:0',
            'quantity' => 'integer|min:1|max:999',
        ]);

        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $slotIndex = $request->input('slot_index');
        $quantity = $request->input('quantity', 1);
        
        $result = $inventory->removeItem($slotIndex, $quantity);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'inventory' => $inventory->getInventoryData(),
            'removed_quantity' => $result['removed_quantity'] ?? 0,
        ]);
    }

    public function useItem(Request $request): JsonResponse
    {
        $request->validate([
            'slot_index' => 'required|integer|min:0',
        ]);

        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $slotIndex = $request->input('slot_index');
        $result = $inventory->useItem($slotIndex, $character);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'effects' => $result['effects'] ?? [],
            'inventory' => $inventory->getInventoryData(),
            'character' => $result['character'] ?? $character->getStatusSummary(),
        ]);
    }

    public function expandSlots(Request $request): JsonResponse
    {
        $request->validate([
            'additional_slots' => 'required|integer|min:1|max:20',
        ]);

        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $additionalSlots = $request->input('additional_slots');
        $inventory->expandSlots($additionalSlots);

        return response()->json([
            'success' => true,
            'message' => "インベントリが{$additionalSlots}枠拡張されました",
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    public function getItemsByCategory(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        $categoryValue = $request->input('category');
        
        try {
            $category = ItemCategory::from($categoryValue);
            $items = Item::getItemsByCategory($category);
            
            return response()->json([
                'success' => true,
                'category' => $category->getDisplayName(),
                'items' => $items,
            ]);
        } catch (\ValueError $e) {
            return response()->json([
                'success' => false,
                'message' => '無効なカテゴリーです',
            ]);
        }
    }

    public function addSampleItems(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $inventory->addSampleItems();

        return response()->json([
            'success' => true,
            'message' => 'サンプルアイテムを追加しました',
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    public function clearInventory(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $inventory->setSlotData([]);

        return response()->json([
            'success' => true,
            'message' => 'インベントリをクリアしました',
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    public function getItemInfo(Request $request): JsonResponse
    {
        $request->validate([
            'item_name' => 'required|string',
        ]);

        $item = Item::findSampleItem($request->input('item_name'));
        
        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'アイテムが見つかりません',
            ]);
        }

        return response()->json([
            'success' => true,
            'item' => $item->getItemInfo(),
        ]);
    }

    public function moveItem(Request $request): JsonResponse
    {
        $request->validate([
            'from_slot' => 'required|integer|min:0',
            'to_slot' => 'required|integer|min:0',
        ]);

        $character = $this->getOrCreateCharacter();
        $inventory = $this->getOrCreateInventory($character);
        
        $fromSlot = $request->input('from_slot');
        $toSlot = $request->input('to_slot');
        
        $slots = $inventory->getSlotData();
        
        if ($fromSlot >= $inventory->getMaxSlots() || $toSlot >= $inventory->getMaxSlots()) {
            return response()->json([
                'success' => false,
                'message' => '無効なスロット番号です',
            ]);
        }

        if (!isset($slots[$fromSlot]) || empty($slots[$fromSlot])) {
            return response()->json([
                'success' => false,
                'message' => '移動元のスロットにアイテムがありません',
            ]);
        }

        // スロットの内容を交換
        $temp = $slots[$fromSlot] ?? null;
        $slots[$fromSlot] = $slots[$toSlot] ?? null;
        $slots[$toSlot] = $temp;

        // 空のスロットを削除
        if (empty($slots[$fromSlot])) {
            unset($slots[$fromSlot]);
        }

        $inventory->setSlotData($slots);

        return response()->json([
            'success' => true,
            'message' => 'アイテムを移動しました',
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    private function getOrCreateCharacter(): Character
    {
        return Character::createNewCharacter('冒険者');
    }

    private function getOrCreateInventory(Character $character): Inventory
    {
        return Inventory::createForCharacter($character->id ?? 1);
    }
}