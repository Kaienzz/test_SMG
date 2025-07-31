<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Player;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        if ($player->location_type !== 'town') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '道具屋は町にのみ存在します。'
                ], 400);
            }
            
            return redirect('/game')->with('error', '道具屋は町にのみ存在します。');
        }

        $shop = Shop::findByLocation($player->location_id, $player->location_type);
        
        if (!$shop) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'この町には道具屋がありません。'
                ], 404);
            }
            
            return redirect('/game')->with('error', 'この町には道具屋がありません。');
        }

        $shopItems = $shop->availableItems()->with('item')->get();

        return view('shop.index', [
            'shop' => $shop,
            'shopItems' => $shopItems,
            'player' => $player,
            'currentLocation' => [
                'type' => $player->location_type,
                'id' => $player->location_id,
            ],
        ]);
    }

    public function purchase(Request $request): JsonResponse
    {
        $request->validate([
            'shop_item_id' => 'required|exists:shop_items,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $shopItem = ShopItem::with(['shop', 'item'])->find($request->shop_item_id);
        $quantity = $request->quantity ?? 1;

        if (!$shopItem || !$shopItem->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'このアイテムは販売されていません。'
            ], 400);
        }

        if (!$shopItem->isInStock()) {
            return response()->json([
                'success' => false,
                'message' => 'このアイテムは在庫切れです。'
            ], 400);
        }

        $totalPrice = $shopItem->price * $quantity;
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();

        if (!$player->hasGold($totalPrice)) {
            return response()->json([
                'success' => false,
                'message' => "Gが足りません。必要: {$totalPrice}G, 所持: {$player->gold}G"
            ], 400);
        }

        if (!$shopItem->decreaseStock($quantity)) {
            return response()->json([
                'success' => false,
                'message' => '在庫が不足しています。'
            ], 400);
        }

        if (!$player->spendGold($totalPrice)) {
            $shopItem->stock += $quantity;
            $shopItem->save();
            
            return response()->json([
                'success' => false,
                'message' => 'Gの支払いに失敗しました。'
            ], 400);
        }

        $itemService = new ItemService();
        $addResult = $itemService->addItemToInventory($player->id, $shopItem->item_id, $quantity);

        if (!$addResult['success']) {
            $shopItem->stock += $quantity;
            $shopItem->save();
            $player->addGold($totalPrice);
            
            return response()->json([
                'success' => false,
                'message' => $addResult['message']
            ], 400);
        }

        $player->save();

        return response()->json([
            'success' => true,
            'message' => "{$shopItem->item->name} x{$quantity} を {$totalPrice}G で購入しました。",
            'item' => [
                'name' => $shopItem->item->name,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
            ],
            'character' => [
                'remaining_gold' => $player->gold,
            ],
            'shop_item' => [
                'remaining_stock' => $shopItem->stock,
                'is_in_stock' => $shopItem->isInStock(),
            ],
        ]);
    }
}
