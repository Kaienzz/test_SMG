<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Player;
use App\Enums\ShopType;
use App\Services\AlchemyShopService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AlchemyShopController extends Controller
{
    protected AlchemyShopService $alchemyService;

    public function __construct()
    {
        $this->alchemyService = new AlchemyShopService();
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        if (!$this->alchemyService->canEnterShop($player->location_id, $player->location_type)) {
            return $this->handleLocationError($request, '錬金屋は町にのみ存在します。');
        }

        $shop = Shop::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            ShopType::ALCHEMY_SHOP->value
        );
        
        if (!$shop) {
            return $this->handleShopNotFoundError($request, 'この町には錬金屋がありません。');
        }

        // 錬金ショップデータを取得
        $shopData = $this->alchemyService->getShopData($shop);
        
        // プレイヤーの錬金可能アイテムと素材を取得
        $alchemizableItems = $this->alchemyService->getAlchemizableItems($player);
        $materialItems = $this->alchemyService->getMaterialItems($player);
        
        // 現在の場所情報を取得
        $currentLocation = [
            'id' => $player->location_id,
            'type' => $player->location_type,
            'name' => $player->location_id === 'town_a' ? 'A町' : 'B町'
        ];

        return view('shops.alchemy.index', [
            'shop' => $shop,
            'shopData' => $shopData,
            'player' => $player,
            'currentLocation' => $currentLocation,
            'shopType' => ShopType::ALCHEMY_SHOP,
            'alchemizableItems' => $alchemizableItems,
            'materialItems' => $materialItems,
            'availableMaterials' => $shopData['available_materials'],
        ]);
    }

    public function performAlchemy(Request $request): JsonResponse
    {
        $validationRules = [
            'base_item_slot' => 'required|integer|min:0',
            'material_slots' => 'required|array|min:1|max:5',
            'material_slots.*' => 'integer|min:0',
        ];
        
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $shop = Shop::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            ShopType::ALCHEMY_SHOP->value
        );

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => '錬金屋が見つかりません。'
            ], 404);
        }

        $result = $this->alchemyService->processTransaction($shop, $player, $request->all());

        return response()->json($result, $result['success'] ? 200 : ($result['code'] ?? 400));
    }

    /**
     * 錬金プレビュー（実際に錬金せずに結果を予測）
     */
    public function previewAlchemy(Request $request): JsonResponse
    {
        $validationRules = [
            'base_item_slot' => 'required|integer|min:0',
            'material_slots' => 'required|array|min:1|max:5',
            'material_slots.*' => 'integer|min:0',
        ];
        
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $baseItemSlot = $request->input('base_item_slot');
        $materialSlots = $request->input('material_slots', []);

        // ベースアイテムの取得
        $inventory = $player->getInventory();
        $inventoryData = $inventory->getInventoryData();
        
        if (!isset($inventoryData[$baseItemSlot]) || isset($inventoryData[$baseItemSlot]['empty'])) {
            return response()->json([
                'success' => false,
                'message' => 'ベースアイテムが見つかりません。'
            ]);
        }

        $baseItemData = $inventoryData[$baseItemSlot];

        // 素材の検証と効果計算
        $materialNames = [];
        foreach ($materialSlots as $materialSlot) {
            if (!isset($inventoryData[$materialSlot]) || isset($inventoryData[$materialSlot]['empty'])) {
                return response()->json([
                    'success' => false,
                    'message' => '素材アイテムが見つかりません。'
                ]);
            }
            $materialData = $inventoryData[$materialSlot];
            $materialItem = $materialData['item_info'] ?? [];
            $materialNames[] = $materialItem['name'] ?? '';
        }

        if (empty($materialNames)) {
            return response()->json([
                'success' => false,
                'message' => '素材を選択してください。'
            ]);
        }

        // 素材効果を計算
        $materialEffects = \App\Models\AlchemyMaterial::calculateCombinedEffects($materialNames);
        
        return response()->json([
            'success' => true,
            'base_item' => $baseItemData['item_info'] ?? [],
            'material_effects' => $materialEffects,
            'estimated_stats' => $this->calculateEstimatedStats(
                $baseItemData['item_info'] ?? [], 
                $materialEffects
            ),
        ]);
    }

    /**
     * 錬金結果の推定値を計算
     */
    private function calculateEstimatedStats(array $baseItem, array $materialEffects): array
    {
        $baseStats = $baseItem['effects'] ?? [];
        $combinedStats = $materialEffects['combined_stats'];
        
        $estimatedMin = [];
        $estimatedMax = [];
        
        // 基本ステータス + 素材効果を基準値とする
        foreach ($baseStats as $stat => $value) {
            $materialBonus = $combinedStats[$stat] ?? 0;
            $baseWithMaterial = $value + $materialBonus;
            
            // 通常品の範囲（90-110%）
            $estimatedMin[$stat] = max(1, (int)round($baseWithMaterial * 0.9));
            $estimatedMax[$stat] = max(1, (int)round($baseWithMaterial * 1.1));
        }
        
        // 素材にのみ存在するステータスも追加
        foreach ($combinedStats as $stat => $value) {
            if (!isset($estimatedMin[$stat]) && $value > 0) {
                $estimatedMin[$stat] = max(1, (int)round($value * 0.9));
                $estimatedMax[$stat] = max(1, (int)round($value * 1.1));
            }
        }
        
        // 名匠品の場合の範囲（120-150%）
        $masterworkMin = [];
        $masterworkMax = [];
        
        foreach ($baseStats as $stat => $value) {
            $materialBonus = $combinedStats[$stat] ?? 0;
            $baseWithMaterial = $value + $materialBonus;
            
            $masterworkMin[$stat] = max(1, (int)round($baseWithMaterial * 1.2));
            $masterworkMax[$stat] = max(1, (int)round($baseWithMaterial * 1.5));
        }
        
        foreach ($combinedStats as $stat => $value) {
            if (!isset($masterworkMin[$stat]) && $value > 0) {
                $masterworkMin[$stat] = max(1, (int)round($value * 1.2));
                $masterworkMax[$stat] = max(1, (int)round($value * 1.5));
            }
        }
        
        return [
            'normal' => [
                'min' => $estimatedMin,
                'max' => $estimatedMax,
            ],
            'masterwork' => [
                'min' => $masterworkMin,
                'max' => $masterworkMax,
            ],
            'masterwork_chance' => $materialEffects['total_masterwork_chance'],
        ];
    }

    protected function handleLocationError(Request $request, string $message): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 400);
        }
        
        return redirect('/game')->with('error', $message);
    }

    protected function handleShopNotFoundError(Request $request, string $message): View|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 404);
        }
        
        return redirect('/game')->with('error', $message);
    }
}