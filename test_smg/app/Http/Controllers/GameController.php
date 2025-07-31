<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Domain\Location\LocationService;
use App\Application\Services\GameDisplayService;
use App\Application\Services\GameStateManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GameController extends Controller
{
    use \App\Http\Controllers\Traits\HasCharacter;
    
    public function __construct(
        private readonly LocationService $locationService,
        private readonly GameDisplayService $gameDisplayService,
        private readonly GameStateManager $gameStateManager
    ) {}
    
    public function index(): View
    {
        // Database-First: 認証ユーザーのプレイヤーを取得または作成
        $player = $this->getOrCreatePlayer();
        
        // セッション→DB移行: GameStateManagerに委譲
        $this->gameStateManager->migrateSessionToDatabase($player);
        
        // GameDisplayService で View用データを統一準備（DTO使用）
        $gameViewDto = $this->gameDisplayService->prepareGameView($player);
        
        return view('game.index', $gameViewDto->toArray());
    }
    
    
    
    public function rollDice(Request $request): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        $diceResult = $this->gameStateManager->rollDice($player);
        
        return response()->json($diceResult->toArray());
    }
    
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'required|in:left,right,forward,backward',
            'steps' => 'required|integer|min:1|max:30'
        ]);
        
        $player = $this->getOrCreatePlayer();
        $moveResult = $this->gameStateManager->movePlayer($player, $request);
        
        return response()->json($moveResult->toArray());
    }
    
    public function moveToNext(Request $request): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        $result = $this->gameStateManager->moveToNextLocation($player);
        
        return response()->json($result->toArray());
    }
    
    public function moveDirectly(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'nullable|string|in:straight,left,right,back',
            'town_direction' => 'nullable|string|in:north,south,east,west'
        ]);
        
        $player = $this->getOrCreatePlayer();
        $direction = $request->input('direction');
        $townDirection = $request->input('town_direction');
        $result = $this->gameStateManager->moveDirectly($player, $direction, $townDirection);
        
        return response()->json($result->toArray());
    }
    
    public function moveToBranch(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'required|string|in:straight,left,right,back'
        ]);
        
        $player = $this->getOrCreatePlayer();
        $direction = $request->input('direction');
        $result = $this->gameStateManager->moveToBranch($player, $direction);
        
        return response()->json($result->toArray());
    }
    
    public function moveToDirection(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'required|string|in:north,south,east,west'
        ]);
        
        $player = $this->getOrCreatePlayer();
        $direction = $request->input('direction');
        $result = $this->gameStateManager->moveToDirection($player, $direction);
        
        return response()->json($result->toArray());
    }
    
    public function reset(): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        $result = $this->gameStateManager->resetGameState($player);
        
        return response()->json($result->toArray());
    }
    
    /**
     * 指定された場所の施設データを取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLocationShops(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => 'required|string',
            'location_type' => 'required|string|in:town,road'
        ]);
        
        $locationId = $request->input('location_id');
        $locationType = $request->input('location_type');
        
        // Shopモデルから指定された場所の施設を取得
        $shops = \App\Models\Shop::getShopsByLocation($locationId, $locationType);
        
        return response()->json([
            'success' => true,
            'shops' => $shops->map(function($shop) {
                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'shop_type' => $shop->shop_type,
                    'description' => $shop->description,
                    'location_id' => $shop->location_id,
                    'location_type' => $shop->location_type
                ];
            })->toArray()
        ]);
    }

}