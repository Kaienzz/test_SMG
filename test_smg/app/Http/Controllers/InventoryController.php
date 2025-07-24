<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Inventory;
use App\Models\Item;
use App\Enums\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(): View
    {
        // Database-First: 認証ユーザーのキャラクターとインベントリを取得
        $character = Auth::user()->getOrCreateCharacter();
        $inventory = Inventory::createForCharacter($character->id);
        
        // セッション→DB移行: 既存セッションデータがあればDBに反映
        $this->migrateInventorySessionToDatabase($inventory, $character->id);
        
        $inventoryData = $inventory->getInventoryData();
        
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
        $character = Auth::user()->getOrCreateCharacter();
        $inventory = Inventory::createForCharacter($character->id);
        
        return response()->json([
            'inventory' => $inventory->getInventoryData(),
            'character' => $character->getStatusSummary(),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'item_name' => 'required|string',
                'quantity' => 'integer|min:1|max:999',
            ]);

            $character = Auth::user()->getOrCreateCharacter();
            $inventory = Inventory::createForCharacter($character->id);
            
            $item = Item::findSampleItem($request->input('item_name'));
            
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'アイテムが見つかりません',
                ]);
            }

            $quantity = $request->input('quantity', 1);
            $result = $inventory->addItem($item, $quantity);
            
            // Save inventory state to database after modification
            if ($result['success']) {
                $inventory->save();
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'inventory' => $inventory->getInventoryData(),
                'added_quantity' => $result['added_quantity'],
                'remaining_quantity' => $result['remaining_quantity'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'エラーが発生しました: ' . $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile()),
            ], 500);
        }
    }

    public function removeItem(Request $request): JsonResponse
    {
        $request->validate([
            'slot_index' => 'required|integer|min:0',
            'quantity' => 'integer|min:1|max:999',
        ]);

        $character = Auth::user()->getOrCreateCharacter();
        $inventory = Inventory::createForCharacter($character->id);
        
        $slotIndex = $request->input('slot_index');
        $quantity = $request->input('quantity', 1);
        
        $result = $inventory->removeItem($slotIndex, $quantity);
        
        // Save inventory state to database after modification
        if ($result['success']) {
            $inventory->save();
        }

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

        $character = Auth::user()->getOrCreateCharacter();
        $inventory = Inventory::createForCharacter($character->id);
        
        $slotIndex = $request->input('slot_index');
        $result = $inventory->useItem($slotIndex, $character);
        
        // Save inventory state to database after modification
        if ($result['success']) {
            $inventory->save();
        }

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

    /**
     * セッションインベントリデータをデータベースに移行する安全なブリッジメソッド
     */
    private function migrateInventorySessionToDatabase(Inventory $inventory, int $characterId): void
    {
        $sessionKey = 'inventory_data_' . $characterId;
        
        // セッションにインベントリデータがある場合はDBに移行
        if (session()->has($sessionKey)) {
            $sessionSlotData = session($sessionKey);
            
            // DBのslot_dataが空の場合のみセッションデータで更新
            if (empty($inventory->getSlotData()) && !empty($sessionSlotData)) {
                $inventory->setSlotData($sessionSlotData);
                $inventory->save();
                
                // セッションデータを削除（移行完了）
                session()->forget($sessionKey);
            }
        }
        
        // Database-First: 初期インベントリデータ設定（空の場合のみ）
        if (empty($inventory->getSlotData())) {
            // 基本的な初期アイテムを設定
            $initialItems = [
                ['slot' => 1, 'name' => '薬草', 'quantity' => 3, 'type' => 'consumable'],
                ['slot' => 2, 'name' => '毒消し草', 'quantity' => 2, 'type' => 'consumable'],
            ];
            
            // Convert to the format expected by the Inventory model
            $slotData = [];
            foreach ($initialItems as $item) {
                $slotData[$item['slot']] = [
                    'item_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'item_info' => [
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'description' => $item['name'] === '薬草' ? 'HPを回復する' : '毒を治す',
                        'effect' => $item['name'] === '薬草' ? 'heal' : 'cure_poison',
                        'value' => $item['name'] === '薬草' ? 30 : 0,
                    ],
                ];
            }
            
            if (!empty($slotData)) {
                $inventory->setSlotData($slotData);
                $inventory->save();
            }
        }
    }
}