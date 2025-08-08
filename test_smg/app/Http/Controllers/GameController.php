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
    
    public function index(Request $request): View
    {
        \Log::info('🚀 [DEBUG] =============== GameController@index START ===============');
        \Log::info('🚀 [DEBUG] Request URL: ' . $request->fullUrl());
        \Log::info('🚀 [DEBUG] User ID: ' . auth()->id());
        
        // Database-First: 認証ユーザーのプレイヤーを取得または作成
        $player = $this->getOrCreatePlayer();
        
        // キャッシュ問題修正: DBから最新データを強制取得
        $player->refresh();
        \Log::info('🚀 [DEBUG] Player retrieved/created (after refresh):', [
            'id' => $player->id,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id, 
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at->toISOString()
        ]);
        
        // セッション詳細分析: Phase 2 Task 2.1
        \Log::info('🚀 [DEBUG] ======= DETAILED SESSION ANALYSIS (Phase 2) =======');
        \Log::info('🚀 [DEBUG] Session basic info:', [
            'session_id' => session()->getId(),
            'all_session_keys' => array_keys(session()->all())
        ]);
        
        // 個別セッションキーの値を詳細チェック
        $sessionKeys = ['location_type', 'location_id', 'game_position', 'player_sp', 'player_gold'];
        foreach ($sessionKeys as $key) {
            if (session()->has($key)) {
                \Log::info("🚀 [DEBUG] Session '{$key}':", ['value' => session($key), 'type' => gettype(session($key))]);
            }
        }
        
        // プレイヤー固有セッションキーをチェック
        $userSessionKeys = [
            "user_" . auth()->id() . "_game_data",
            "player_" . $player->id . "_location",
            "player_" . $player->id . "_state"
        ];
        foreach ($userSessionKeys as $key) {
            if (session()->has($key)) {
                \Log::info("🚀 [DEBUG] User session '{$key}':", ['data' => session($key)]);
            }
        }
        
        // セッション内の全データを出力（セキュリティ上問題ないかチェック）
        $allSessionData = session()->all();
        unset($allSessionData['_token']); // CSRFトークンは除外
        \Log::info('🚀 [DEBUG] All session data (filtered):', $allSessionData);
        
        // セッション→DB移行: GameStateManagerに委譲
        $this->gameStateManager->migrateSessionToDatabase($player);
        
        // 移行後のプレイヤー状態を確認
        $player->refresh();
        \Log::info('🚀 [DEBUG] Player state after migration:', [
            'id' => $player->id,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id,
            'game_position' => $player->game_position,
            'updated_at' => $player->updated_at->toISOString()
        ]);
        
        // セッション状態の変化を確認
        \Log::info('🚀 [DEBUG] ======= SESSION STATE AFTER MIGRATION =======');
        $postMigrationKeys = array_keys(session()->all());
        \Log::info('🚀 [DEBUG] Session keys after migration:', $postMigrationKeys);
        
        // 残っているセッションデータをチェック
        foreach ($sessionKeys as $key) {
            if (session()->has($key)) {
                \Log::info("🚀 [DEBUG] Session '{$key}' still exists:", ['value' => session($key)]);
            }
        }
        
        // GameDisplayService で View用データを統一準備（DTO使用）
        $gameViewDto = $this->gameDisplayService->prepareGameView($player);
        
        // 直接統合レイアウト（noright）を使用
        $viewData = $gameViewDto->toArray();
        \Log::info('🚀 [DEBUG] GameDisplayService results:', [
            'currentLocation' => $viewData['currentLocation'] ?? null,
            'nextLocation' => $viewData['nextLocation'] ?? null,
            'gameState' => $viewData['gameState'] ?? 'unknown'
        ]);
        
        $unifiedData = $this->prepareUnifiedLayoutData($viewData, $player);
        $detectedGameState = $this->detectGameState($player);
        \Log::info('🚀 [DEBUG] Detected game state: ' . $detectedGameState);
        
        \Log::info('🚀 [DEBUG] =============== GameController@index END ===============');
        
        return view('game', $unifiedData);
    }
    
    
    /**
     * 統合レイアウト用のデータ準備
     */
    private function prepareUnifiedLayoutData(array $viewData, $player): array
    {
        // ゲーム状態の検出
        $gameState = $this->detectGameState($player);
        
        // 町の接続情報を追加（町にいる場合のみ）
        $townConnections = null;
        if ($player->location_type === 'town') {
            $townConnections = $this->locationService->getTownConnections($player->location_id);
        }
        
        return [
            'gameState' => $gameState,
            'player' => $player,
            'character' => $player, // 下位互換性
            'currentLocation' => $viewData['currentLocation'] ?? null,
            'nextLocation' => $viewData['nextLocation'] ?? null,
            'movementInfo' => $viewData['movementInfo'] ?? [],
            'monster' => $viewData['monster'] ?? null,
            'battle' => $viewData['battle'] ?? null,
            'townConnections' => $townConnections, // 町の実際の接続情報
        ];
    }
    
    /**
     * プレイヤーの現在の状況からゲーム状態を検出
     */
    private function detectGameState($player): string
    {
        // 戦闘中かチェック
        if (session('battle_active') || ($player->current_location_type ?? null) === 'battle') {
            return 'battle';
        }
        
        // 現在の場所のタイプで判定
        $locationType = $player->current_location_type ?? $player->location_type ?? 'town';
        
        return match($locationType) {
            'road' => 'road',
            'battle' => 'battle',
            default => 'town'
        };
    }
    
    
    
    public function rollDice(Request $request): JsonResponse
    {
        $player = $this->getOrCreatePlayer();
        
        // Check if player is on a road (can roll dice)
        $locationType = $player->location_type ?? 'town';
        if ($locationType !== 'road') {
            return response()->json([
                'success' => false,
                'error' => '町にいるときはサイコロを振ることができません。道路に移動してください。',
                'location_type' => $locationType
            ], 400);
        }
        
        $diceResult = $this->gameStateManager->rollDice($player);
        
        $response = $diceResult->toArray();
        $response['success'] = true;
        
        return response()->json($response);
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
        
        // セッション詳細分析: 移動処理前
        \Log::info('🚀 [DEBUG] ======= SESSION BEFORE MOVE_TO_NEXT =======');
        $sessionBeforeMove = session()->all();
        unset($sessionBeforeMove['_token']);
        \Log::info('🚀 [DEBUG] Session before movement:', $sessionBeforeMove);
        
        $result = $this->gameStateManager->moveToNextLocation($player);
        
        // セッション詳細分析: 移動処理後
        \Log::info('🚀 [DEBUG] ======= SESSION AFTER MOVE_TO_NEXT =======');
        $sessionAfterMove = session()->all();
        unset($sessionAfterMove['_token']);
        \Log::info('🚀 [DEBUG] Session after movement:', $sessionAfterMove);
        
        // セッションの変化を検出
        $addedKeys = array_diff(array_keys($sessionAfterMove), array_keys($sessionBeforeMove));
        $removedKeys = array_diff(array_keys($sessionBeforeMove), array_keys($sessionAfterMove));
        if (!empty($addedKeys) || !empty($removedKeys)) {
            \Log::info('🚀 [DEBUG] Session changes detected:', [
                'added_keys' => $addedKeys,
                'removed_keys' => $removedKeys
            ]);
        }
        
        // プレイヤー情報をレスポンスに追加
        $response = $result->toArray();
        $response['player'] = [
            'game_position' => $player->game_position,
            'location_type' => $player->location_type,
            'location_id' => $player->location_id
        ];
        
        return response()->json($response);
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