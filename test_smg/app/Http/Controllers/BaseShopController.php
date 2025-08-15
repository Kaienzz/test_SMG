<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Enums\ShopType;
use App\Contracts\ShopServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

abstract class BaseShopController extends Controller
{
    protected ShopType $shopType;
    protected ShopServiceInterface $shopService;

    public function __construct(ShopType $shopType, ShopServiceInterface $shopService)
    {
        $this->shopType = $shopType;
        $this->shopService = $shopService;
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        if (!$this->shopService->canEnterShop($player->location_id, $player->location_type)) {
            return $this->handleLocationError($request, $this->shopType->getDisplayName() . 'は町にのみ存在します。');
        }

        $shop = Shop::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            $this->shopType->value
        );
        
        if (!$shop) {
            return $this->handleShopNotFoundError($request, 'この町には' . $this->shopType->getDisplayName() . 'がありません。');
        }

        // $player は既に上で取得済み
        $shopData = $this->shopService->getShopData($shop);
        
        // 現在の場所情報を取得
        $locationNames = [
            'town_prima' => 'プリマ',
            'town_a' => 'A町', // 下位互換性のため
            'town_b' => 'B町',
            'town_c' => 'C町',
            'elven_village' => 'エルフの村',
            'merchant_city' => '商業都市'
        ];
        
        $currentLocation = [
            'id' => $player->location_id,
            'type' => $player->location_type,
            'name' => $locationNames[$player->location_id] ?? '未知の場所'
        ];

        return view($this->shopType->getViewPrefix() . '.index', [
            'shop' => $shop,
            'shopData' => $shopData,
            'player' => $player,
            'currentLocation' => $currentLocation,
            'shopType' => $this->shopType,
        ]);
    }

    public function processTransaction(Request $request): JsonResponse
    {
        $validationRules = $this->getValidationRules();
        $request->validate($validationRules);

        $user = Auth::user();
        $player = $user->getOrCreatePlayer();
        
        $shop = Shop::findByLocationAndType(
            $player->location_id, 
            $player->location_type,
            $this->shopType->value
        );

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => $this->shopType->getDisplayName() . 'が見つかりません。'
            ], 404);
        }

        $result = $this->shopService->processTransaction($shop, $player, $request->all());

        return response()->json($result, $result['success'] ? 200 : ($result['code'] ?? 400));
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

    abstract protected function getValidationRules(): array;
}