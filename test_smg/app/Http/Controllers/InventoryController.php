<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Inventory;
use App\Models\Item;
use App\Enums\ItemCategory;
use App\Services\StandardItem\StandardItemService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(): View
    {
        // Database-First: 認証ユーザーのプレイヤーとインベントリを取得
        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        // セッション→DB移行: 既存セッションデータがあればDBに反映
        $this->migrateInventorySessionToDatabase($inventory, $player->id);
        
        $inventoryData = $inventory->getInventoryData();
        
        // 標準アイテムを取得
        $standardItemService = new StandardItemService();
        $standardItems = $standardItemService->getStandardItems();
        
        // インベントリ画面用にフォーマット
        $sampleItems = collect($standardItems)->map(function($item) {
            return [
                'name' => $item['name'],
                'category' => $item['category'],
                'category_name' => $this->getCategoryDisplayName($item['category']),
                'description' => $item['description'] ?? '',
                'emoji' => $item['emoji'] ?? '📦',
                'value' => $item['value'] ?? 0,
            ];
        })->toArray();
        
        return view('inventory.index', [
            'player' => $player,
            'inventoryData' => $inventoryData,
            'categories' => [],
            'sampleItems' => $sampleItems,
        ]);
    }

    public function show(): JsonResponse
    {
        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        return response()->json([
            'inventory' => $inventory->getInventoryData(),
            'character' => [
                'name' => $player->name,
                'level' => $player->level ?? 1,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'mp' => $player->mp,
                'max_mp' => $player->max_mp,
                'sp' => $player->sp,
                'max_sp' => $player->max_sp,
                'hp_percentage' => $player->getHpPercentage(),
                'sp_percentage' => $player->getSpPercentage(),
                'mp_percentage' => $player->getMpPercentage(),
                'is_alive' => $player->isAlive(),
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'item_name' => 'required|string',
                'quantity' => 'integer|min:1|max:999',
            ]);

            $player = Auth::user()->getOrCreatePlayer();
            $inventory = Inventory::createForPlayer($player->id);
            
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

        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
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

        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        $slotIndex = $request->input('slot_index');
        $result = $inventory->useItem($slotIndex, $player);
        
        // Save inventory state and player state to database after modification
        if ($result['success']) {
            $inventory->save();
            $player->save(); // プレイヤーのHP/MP/SPステータスを保存
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'effects' => $result['effects'] ?? [],
            'inventory' => $inventory->getInventoryData(),
            'character' => [
                'name' => $player->name,
                'level' => $player->level ?? 1,
                'hp' => $player->hp,
                'max_hp' => $player->max_hp,
                'mp' => $player->mp,
                'max_mp' => $player->max_mp,
                'sp' => $player->sp,
                'max_sp' => $player->max_sp,
                'hp_percentage' => $player->getHpPercentage(),
                'sp_percentage' => $player->getSpPercentage(),
                'mp_percentage' => $player->getMpPercentage(),
                'is_alive' => $player->isAlive(),
            ],
        ]);
    }

    public function expandSlots(Request $request): JsonResponse
    {
        $request->validate([
            'additional_slots' => 'required|integer|min:1|max:20',
        ]);

        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        $additionalSlots = $request->input('additional_slots');
        $inventory->expandSlots($additionalSlots);
        $inventory->save(); // データベースに保存

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
        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        $inventory->addSampleItems();
        $inventory->save(); // データベースに保存

        return response()->json([
            'success' => true,
            'message' => 'サンプルアイテムを追加しました',
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    public function clearInventory(): JsonResponse
    {
        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
        $inventory->setSlotData([]);
        $inventory->save(); // データベースに保存

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

        $player = Auth::user()->getOrCreatePlayer();
        $inventory = Inventory::createForPlayer($player->id);
        
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
        $inventory->save(); // データベースに保存

        return response()->json([
            'success' => true,
            'message' => 'アイテムを移動しました',
            'inventory' => $inventory->getInventoryData(),
        ]);
    }

    /**
     * カテゴリー表示名を取得
     */
    private function getCategoryDisplayName(string $category): string
    {
        $categoryMap = [
            'potion' => 'ポーション',
            'weapon' => '武器',
            'body_equipment' => '胴体装備',
            'head_equipment' => '頭装備',
            'leg_equipment' => '脚装備',
            'accessory' => 'アクセサリー',
            'material' => '素材',
            'tool' => '道具',
            'other' => 'その他',
        ];
        
        return $categoryMap[$category] ?? 'その他';
    }

    /**
     * セッションインベントリデータをデータベースに移行する安全なブリッジメソッド
     */
    private function migrateInventorySessionToDatabase(Inventory $inventory, int $playerId): void
    {
        $sessionKey = 'inventory_data_' . $playerId;
        
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
        
    }
}