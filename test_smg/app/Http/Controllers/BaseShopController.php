<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Character;
use App\Services\DummyDataService;
use App\Enums\ShopType;
use App\Contracts\ShopServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

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
        $player = DummyDataService::getPlayer();
        $currentLocation = DummyDataService::getCurrentLocation();
        
        if (!$this->shopService->canEnterShop($player['current_location_id'], $player['current_location_type'])) {
            return $this->handleLocationError($request, $this->shopType->getDisplayName() . 'は町にのみ存在します。');
        }

        $shop = Shop::findByLocationAndType(
            $player['current_location_id'], 
            $player['current_location_type'],
            $this->shopType->value
        );
        
        if (!$shop) {
            return $this->handleShopNotFoundError($request, 'この町には' . $this->shopType->getDisplayName() . 'がありません。');
        }

        $character = DummyDataService::getCharacter();
        $shopData = $this->shopService->getShopData($shop);

        return view($this->shopType->getViewPrefix() . '.index', [
            'shop' => $shop,
            'shopData' => $shopData,
            'character' => (object) $character,
            'currentLocation' => $currentLocation,
            'shopType' => $this->shopType,
        ]);
    }

    public function processTransaction(Request $request): JsonResponse
    {
        $validationRules = $this->getValidationRules();
        $request->validate($validationRules);

        $player = DummyDataService::getPlayer();
        $shop = Shop::findByLocationAndType(
            $player['current_location_id'], 
            $player['current_location_type'],
            $this->shopType->value
        );

        if (!$shop) {
            return response()->json([
                'success' => false,
                'message' => $this->shopType->getDisplayName() . 'が見つかりません。'
            ], 404);
        }

        $character = Character::find(1); // Dummy character
        if (!$character) {
            return response()->json([
                'success' => false,
                'message' => 'キャラクターが見つかりません。'
            ], 400);
        }

        $result = $this->shopService->processTransaction($shop, $character, $request->all());

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