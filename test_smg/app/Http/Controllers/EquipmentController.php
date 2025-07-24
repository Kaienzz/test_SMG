<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EquipmentController extends Controller
{
    public function show(Request $request): View
    {
        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $equipment = $this->getOrCreateEquipment($character->id);
        $inventory = $character->getInventory();
        
        $equippedItems = $equipment->getEquippedItems();
        $totalStats = $equipment->getTotalStats();
        $inventoryItems = $inventory->getItems();
        $sampleEquipmentItems = Equipment::getSampleEquipmentItems();

        return view('equipment.index', compact(
            'character',
            'equippedItems',
            'totalStats',
            'inventoryItems',
            'sampleEquipmentItems'
        ));
    }

    public function equip(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|integer',
            'slot' => 'required|string|in:weapon,body_armor,shield,helmet,boots,accessory',
        ]);

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $equipment = $this->getOrCreateEquipment($character->id);
        $inventory = $character->getInventory();
        
        $itemId = $request->item_id;
        $slot = $request->slot;
        
        // インベントリからアイテムを確認
        if (!$inventory->hasItem($itemId)) {
            return response()->json([
                'success' => false,
                'message' => 'そのアイテムを持っていません。'
            ], 400);
        }
        
        // 現在装備しているアイテムを外してインベントリに戻す
        $currentEquippedItemId = $this->getCurrentEquippedItemId($equipment, $slot);
        if ($currentEquippedItemId) {
            $inventory->addItem($currentEquippedItemId, 1);
        }
        
        // 新しいアイテムを装備
        $item = Item::find($itemId);
        if (!$item || !$equipment->equipItem($item, $slot)) {
            return response()->json([
                'success' => false,
                'message' => 'そのアイテムはこのスロットに装備できません。'
            ], 400);
        }
        
        // インベントリからアイテムを削除
        $inventory->removeItem($itemId, 1);

        return response()->json([
            'success' => true,
            'message' => "アイテムを装備しました。",
            'equipped_items' => $equipment->getEquippedItems(),
            'total_stats' => $equipment->getTotalStats(),
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    public function unequip(Request $request): JsonResponse
    {
        $request->validate([
            'slot' => 'required|string|in:weapon,body_armor,shield,helmet,boots,accessory',
        ]);

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $slot = $request->slot;

        $equipment = $this->getOrCreateEquipment($character->id);
        $inventory = $character->getInventory();

        $currentEquippedItemId = $this->getCurrentEquippedItemId($equipment, $slot);
        
        if (!$currentEquippedItemId) {
            return response()->json([
                'success' => false,
                'message' => 'そのスロットには何も装備されていません。'
            ], 400);
        }

        if ($equipment->unequipSlot($slot)) {
            $inventory->addItem($currentEquippedItemId, 1);

            return response()->json([
                'success' => true,
                'message' => 'アイテムを外しました。',
                'equipped_items' => $equipment->getEquippedItems(),
                'total_stats' => $equipment->getTotalStats(),
                'inventory' => $inventory->getInventoryData(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => '装備を外すことができませんでした。'
        ], 400);
    }

    public function getAvailableItems(Request $request): JsonResponse
    {
        $slot = $request->query('slot');

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $inventory = $character->getInventory();
        $items = $inventory->getItems();

        if ($slot) {
            $categoryFilter = $this->getSlotCategoryFilter($slot);
            if ($categoryFilter) {
                $items = array_filter($items, function($inventoryItem) use ($categoryFilter) {
                    $item = $inventoryItem['item'];
                    return in_array($item['category'], $categoryFilter);
                });
            }
        }

        return response()->json([
            'success' => true,
            'items' => array_values($items),
        ]);
    }

    private function getOrCreateEquipment(int $characterId): Equipment
    {
        $equipment = Equipment::where('character_id', $characterId)->first();
        
        if (!$equipment) {
            $equipment = Equipment::createForCharacter($characterId);
        }

        return $equipment;
    }

    private function getCurrentEquippedItemId(Equipment $equipment, string $slot): ?int
    {
        $slotColumnMap = [
            'weapon' => 'weapon_id',
            'body_armor' => 'body_armor_id',
            'shield' => 'shield_id',
            'helmet' => 'helmet_id',
            'boots' => 'boots_id',
            'accessory' => 'accessory_id',
        ];

        $column = $slotColumnMap[$slot] ?? null;
        return $column ? $equipment->$column : null;
    }

    private function getSlotCategoryFilter(string $slot): ?array
    {
        $slotCategoryMap = [
            'weapon' => ['weapon'],
            'body_armor' => ['body_equipment'],
            'shield' => ['shield'],
            'helmet' => ['head_equipment'],
            'boots' => ['foot_equipment'],
            'accessory' => ['accessory'],
        ];

        return $slotCategoryMap[$slot] ?? null;
    }

    public function addSampleEquipment(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'required|string',
            'item_name' => 'required|string',
        ]);

        $user = Auth::user();
        $character = $user->getOrCreateCharacter();
        $inventory = $character->getInventory();
        $itemName = $request->item_name;

        // TODO: サンプル装備の実際の追加処理を実装
        // 現在はサンプルなので成功レスポンスのみ

        return response()->json([
            'success' => true,
            'message' => "{$itemName}をインベントリに追加しました。",
            'inventory' => $inventory->getInventoryData(),
        ]);
    }
}