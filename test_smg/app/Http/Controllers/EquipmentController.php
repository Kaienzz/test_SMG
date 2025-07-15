<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Equipment;
use App\Models\Item;
use App\Models\Inventory;
use App\Services\DummyDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function show(Request $request): View
    {
        $characterId = $request->query('character_id', 1);
        $character = (object) DummyDataService::getCharacter($characterId);
        $equippedItems = DummyDataService::getEquippedItems($characterId);
        $totalStats = DummyDataService::getEquipmentTotalStats($characterId);
        $inventoryData = DummyDataService::getInventory($characterId);
        $inventoryItems = $inventoryData['items'];
        $sampleEquipmentItems = DummyDataService::getSampleEquipmentItems();

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
            'character_id' => 'required|integer',
            'item_id' => 'required|integer',
            'slot' => 'required|string|in:weapon,body_armor,shield,helmet,boots,accessory',
        ]);

        return response()->json([
            'success' => true,
            'message' => "アイテムを装備しました。",
            'equipped_items' => DummyDataService::getEquippedItems($request->character_id),
            'total_stats' => DummyDataService::getEquipmentTotalStats($request->character_id),
            'inventory' => DummyDataService::getInventory($request->character_id),
        ]);
    }

    public function unequip(Request $request): JsonResponse
    {
        $request->validate([
            'character_id' => 'required|integer',
            'slot' => 'required|string|in:weapon,body_armor,shield,helmet,boots,accessory',
        ]);

        $characterId = $request->character_id;
        $slot = $request->slot;

        $character = Character::findOrFail($characterId);
        $equipment = $this->getOrCreateEquipment($characterId);
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
        $characterId = $request->query('character_id', 1);
        $slot = $request->query('slot');

        $character = Character::findOrFail($characterId);
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
            'character_id' => 'required|integer',
            'category' => 'required|string',
            'item_name' => 'required|string',
        ]);

        $itemName = $request->item_name;

        return response()->json([
            'success' => true,
            'message' => "{$itemName}をインベントリに追加しました。",
            'inventory' => DummyDataService::getInventory($request->character_id),
        ]);
    }
}