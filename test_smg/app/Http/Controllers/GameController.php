<?php

namespace App\Http\Controllers;

use App\Models\Character;
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
        // Database-First: 認証ユーザーのキャラクターを取得または作成
        $character = $this->getOrCreateCharacter();
        
        // セッション→DB移行: GameStateManagerに委譲
        $this->gameStateManager->migrateSessionToDatabase($character);
        
        // GameDisplayService で View用データを統一準備（DTO使用）
        $gameViewDto = $this->gameDisplayService->prepareGameView($character);
        
        return view('game.index', $gameViewDto->toArray());
    }
    
    
    
    public function rollDice(Request $request): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $result = $this->gameStateManager->rollDice($character);
        
        return response()->json($result);
    }
    
    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'direction' => 'required|in:left,right,forward,backward',
            'steps' => 'required|integer|min:1|max:30'
        ]);
        
        $character = $this->getOrCreateCharacter();
        $moveResult = $this->gameStateManager->moveCharacter($character, $request);
        
        return response()->json($moveResult->toArray());
    }
    
    public function moveToNext(Request $request): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $result = $this->gameStateManager->moveToNextLocation($character);
        
        return response()->json($result->toArray());
    }
    
    public function reset(): JsonResponse
    {
        $character = $this->getOrCreateCharacter();
        $result = $this->gameStateManager->resetGameState($character);
        
        return response()->json($result->toArray());
    }
    

}