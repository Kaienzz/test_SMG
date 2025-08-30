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
        
        // 利用可能な接続情報を追加（町・道路両方で取得）
        $availableConnections = [];
        try {
            $availableConnections = $this->locationService->getAvailableConnectionsWithData($player);
            \Log::info('Available connections loaded', [
                'player_id' => $player->id,
                'location_type' => $player->location_type,
                'location_id' => $player->location_id,
                'game_position' => $player->game_position,
                'connections_count' => count($availableConnections)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load available connections', [
                'error' => $e->getMessage(),
                'player_id' => $player->id
            ]);
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
            'availableConnections' => $availableConnections, // 道路での利用可能接続情報
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
            'direction' => 'required|in:north,south,forward,backward',
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
        // 新旧両システム + left/right対応のバリデーション
        $request->validate([
            'direction' => 'required|string|in:north,south,east,west,left,right,move_north,move_south,move_east,move_west,turn_left,turn_right'
        ]);
        
        $player = $this->getOrCreatePlayer();
        $direction = $request->input('direction');
        
        // left/rightを道路軸に応じて適切な方角に変換
        if ($direction === 'left' || $direction === 'right') {
            $direction = $this->locationService->convertLeftRightToDirection($player, $direction);
        }
        
        // 新システム: action_labelが送信された場合、connection_idベースで移動
        if (str_starts_with($direction, 'move_') || str_starts_with($direction, 'turn_')) {
            return $this->moveByActionLabel($player, $direction);
        }
        
        // 旧システム: 従来の方向移動
        $result = $this->gameStateManager->moveToDirection($player, $direction);
        
        return response()->json($result->toArray());
    }
    
    /**
     * 新システム: action_labelベースでの移動処理
     */
    private function moveByActionLabel(Player $player, string $actionLabel): JsonResponse
    {
        try {
            // 利用可能な接続から該当するaction_labelを検索
            $connections = $this->locationService->getAvailableConnections($player);
            
            $targetConnection = $connections->first(function ($conn) use ($actionLabel) {
                return $conn->action_label === $actionLabel;
            });
            
            if (!$targetConnection) {
                \Log::warning('No connection found for action label', [
                    'player_location' => $player->location_id,
                    'player_position' => $player->game_position,
                    'action_label' => $actionLabel,
                    'available_connections' => $connections->pluck('action_label')->toArray()
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'この方向への移動はできません'
                ]);
            }
            
            // 新システムのmoveToConnectionTargetを使用
            $result = $this->locationService->moveToConnectionTarget($player, $targetConnection->id);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error('Error in moveByActionLabel', [
                'action_label' => $actionLabel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'サーバーエラーが発生しました。管理者にお問い合わせください。'
            ], 500);
        }
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
    public function getLocationFacilities(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => 'required|string',
            'location_type' => 'required|string|in:town,road'
        ]);
        
        $locationId = $request->input('location_id');
        $locationType = $request->input('location_type');
        
        // TownFacilityモデルから指定された場所の施設を取得
        $facilities = \App\Models\TownFacility::getFacilitiesByLocation($locationId, $locationType);
        
        // 接続情報も取得（町の場合のみ）
        $connections = [];
        if ($locationType === 'town') {
            $locationService = app(\App\Domain\Location\LocationService::class);
            $connections = $locationService->getTownConnections($locationId) ?? [];
        }
        
        return response()->json([
            'success' => true,
            'facilities' => $facilities->map(function($facility) {
                return [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'facility_type' => $facility->facility_type,
                    'description' => $facility->description,
                    'location_id' => $facility->location_id,
                    'location_type' => $facility->location_type
                ];
            })->toArray(),
            'connections' => $connections
        ]);
    }
    
    /**
     * Get available connections at current player position (New Logic)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableConnections(Request $request): JsonResponse
    {
        try {
            $player = $this->getOrCreatePlayer();
            $connections = $this->locationService->getAvailableConnectionsWithData($player);
            
            return response()->json([
                'success' => true,
                'connections' => $connections,
                'current_location' => [
                    'type' => $player->location_type,
                    'id' => $player->location_id,
                    'position' => $player->game_position
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get available connections', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '接続情報の取得に失敗しました',
                'connections' => []
            ], 500);
        }
    }
    
    /**
     * Move to specific connection (New Logic)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function moveToConnection(Request $request): JsonResponse
    {
        $request->validate([
            'connection_id' => 'required|string|exists:route_connections,id'
        ]);
        
        try {
            $player = $this->getOrCreatePlayer();
            $connectionId = $request->input('connection_id');
            
            \Log::info('Moving to connection', [
                'player_id' => $player->id,
                'connection_id' => $connectionId,
                'current_position' => [
                    'location_type' => $player->location_type,
                    'location_id' => $player->location_id,
                    'game_position' => $player->game_position
                ]
            ]);
            
            $result = $this->locationService->moveToConnectionTarget($player, $connectionId);
            
            if ($result['success']) {
                \Log::info('Successfully moved to connection', [
                    'player_id' => $player->id,
                    'destination' => $result['destination'],
                    'action_performed' => $result['action_performed']
                ]);
            } else {
                \Log::warning('Failed to move to connection', [
                    'player_id' => $player->id,
                    'error' => $result['error']
                ]);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error('Exception during connection movement', [
                'connection_id' => $request->input('connection_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => '移動に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Move using keyboard shortcut (New Logic)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function moveByKeyboard(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|in:up,down,left,right'
        ]);
        
        try {
            $player = $this->getOrCreatePlayer();
            $key = $request->input('key');
            
            // Find connection with matching keyboard shortcut
            $connection = \App\Models\RouteConnection::where('source_location_id', $player->location_id)
                                                  ->where('keyboard_shortcut', $key)
                                                  ->enabled()
                                                  ->first();
            
            if (!$connection) {
                return response()->json([
                    'success' => false,
                    'error' => 'このキーに割り当てられた移動先がありません'
                ]);
            }
            
            // Check if connection is available at current position
            if (!$this->locationService->shouldShowConnectionAtPosition($connection, $player->game_position ?? 0)) {
                return response()->json([
                    'success' => false,
                    'error' => 'この移動先は現在の位置では利用できません'
                ]);
            }
            
            \Log::info('Moving by keyboard shortcut', [
                'player_id' => $player->id,
                'key' => $key,
                'connection_id' => $connection->id
            ]);
            
            $result = $this->locationService->moveToConnectionTarget($player, $connection->id);
            
            // Add keyboard shortcut info to response
            if ($result['success']) {
                $result['keyboard_shortcut'] = $key;
                $result['keyboard_used'] = true;
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            \Log::error('Exception during keyboard movement', [
                'key' => $request->input('key'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'キーボード移動に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
    

}